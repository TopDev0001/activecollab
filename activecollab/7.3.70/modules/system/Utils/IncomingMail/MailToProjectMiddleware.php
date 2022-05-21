<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\IncomingMail;

use ActiveCollab\Foundation\Mail\Incoming\Message\MessageInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Address\AddressInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\IncomingMailContextPermissionsBounce;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\OperationFailedBounce;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\UnknownIncomingMailContextBounce;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Capture\Capture;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\MiddlewareResultInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Skip\Skip;
use ActiveCollab\Module\Discussions\Events\DataObjectLifeCycleEvents\DiscussionCreatedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskCreatedEvent;
use Angie\Mailer;
use AngieApplication;
use Discussion;
use Discussions;
use Exception;
use IUser;
use LogicException;
use MailToProjectReceivedNotification;
use Project;
use Projects;
use Task;
use Tasks;
use UploadedFile;
use Users;

class MailToProjectMiddleware extends Middleware
{
    public function process(
        MessageInterface $message,
        AddressInterface $matched_recipient,
        string $source
    ): ?MiddlewareResultInterface
    {
        $result = parent::process($message, $matched_recipient, $source);

        if (!empty($result)) {
            return $result;
        }

        $project_hash = $this->getProjectHash($matched_recipient);

        if (empty($project_hash)) {
            return new Skip('Not an email to project.');
        }

        $this->logInfo(
            'Email should be imported as a task or discussion',
            [
                'email_source' => $source,
                'project_hash' => $project_hash,
            ]
        );

        $project = $this->getProject($project_hash);

        if (empty($project)) {
            return new UnknownIncomingMailContextBounce(
                "The email you sent hasn't been posted to ActiveCollab. It's possible that the project has been deleted or you used an incorrect address. Please contact the system administrator to check what went wrong."
            );
        }

        $user = $this->getSenderUser($message);

        if (empty($user)) {
            return new IncomingMailContextPermissionsBounce(
                "The email you sent hasn't been imported. You need to have an account in ActiveCollab. Please contact the system administrator to enable this for you."
            );
        }

        $can_add_tasks = Tasks::canAdd($user, $project);
        $can_add_discussions = Discussions::canAdd($user, $project);

        if (!$can_add_tasks && !$can_add_discussions) {
            return new IncomingMailContextPermissionsBounce(
                "The email you sent hasn't been imported. You need the right permissions to be able to do this. Please contact the system administrator to enable this for you."
            );
        }

        try {
            $context_name = $message->getSubject();
            $context_body = $message->getBody();

            if (mb_strlen($context_name) > 150) {
                $context_name = strtok(wordwrap($context_name, 149), "\n") . '…';
                $context_body = $message->getSubject() . '<br> <br>' . $message->getBody();
            }

            $common_attributes = [
                'project_id' => $project->getId(),
                'name' => $context_name,
                'body' => nl2br($context_body),
                'attach_uploaded_files' => array_map(
                    function (UploadedFile $attachment) {
                        return $attachment->getCode();
                    },
                    $message->getAttachments()
                ),
            ];

            if ($can_add_discussions && !$can_add_tasks) {
                $context = Discussions::create($common_attributes, false, false);
                $context->setCreatedBy($user);
                $context->save();

                $notify_subscribers_about = 'discussions/new_discussion';
            } elseif ($can_add_tasks) {
                $context = Tasks::create($common_attributes, false, false);
                $context->setCreatedBy($user);
                $context->save();

                $notify_subscribers_about = 'tasks/new_task';
            } else {
                throw new LogicException('User does not have permissions to add discussions or tasks.');
            }

            foreach ($message->getRecipients() as $recipient) {
                $subscriber = Users::findByEmail($recipient);

                if ($subscriber && $context->canSubscribe($subscriber)) {
                    $context->subscribe($subscriber);
                }
            }

            $project_leader = $project->getLeader();

            if ($project_leader
                && !in_array($project_leader->getEmail(), $message->getRecipients())
                && $user->isClient()
            ) {
                $context->subscribe($project_leader);
            }

            if ($context instanceof Task) {
                $this->data_object_pool->announce(new TaskCreatedEvent($context));
            } elseif ($context instanceof Discussion) {
                $this->data_object_pool->announce(new DiscussionCreatedEvent($context));
            }

            $this->logInfo(
                'Message has been imported as {object} #{object_id}',
                [
                    'event' => 'task_created_from_email',
                    'email_source' => $source,
                    'matched_recipient' => $matched_recipient->getFullAddress(),
                    'object' => $context->getVerboseType(true),
                    'object_id' => $context->getId(),
                    'subscribers' => array_map(
                        function ($s) {
                            return $s instanceof IUser ? $s->getEmail() : 'not a user';
                        },
                        $context->getSubscribers()
                    ),
                ]
            );

            AngieApplication::notifications()
                ->notifyAbout($notify_subscribers_about, $context, $context->getCreatedBy())
                    ->sendToSubscribers();

            /** @var MailToProjectReceivedNotification $mail_to_project_notification */
            $mail_to_project_notification = AngieApplication::notifications()
                ->notifyAbout('system/mail_to_project_received', $context, Mailer::getDefaultSender());

            $mail_to_project_notification
                ->setProject($project)
                ->setMatchedRecipientAddress($matched_recipient->getFullAddress())
                ->sendToUsers(
                    [
                        $context->getCreatedBy(),
                    ]
                );

            return new Capture();
        } catch (Exception $e) {
            $this->logInfo(
                'Failed to create project task or discussion based on incoming email.',
                [
                    'exception' => $e,
                ]
            );

            return new OperationFailedBounce('Email import operation failed.');
        }
    }

    private function getProjectHash(AddressInterface $matched_recipient): ?string
    {
        return $matched_recipient->getTag();
    }

    private function getProject(string $project_hash): ?Project
    {
        $project = Projects::findOneBySql('SELECT * FROM projects WHERE project_hash = ?', $project_hash);

        if (!$project instanceof Project) {
            $this->logWarning(
                'Email import: Failed to find project with "{project_hash}" project hash.',
                [
                    'project_hash' => $project_hash,
                ]
            );
        }

        return $project;
    }
}
