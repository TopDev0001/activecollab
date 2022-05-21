<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\EmailReplyExtractor;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskCreatedEvent;
use Angie\Mailer;

function tasks_handle_on_email_received(
    IncomingMailMessage $incoming_mail_message,
    string $source,
    string &$bounce
)
{
    $recipients = $incoming_mail_message->getRecipients();
    $references = $incoming_mail_message->getReferences();
    $default_sender = Mailer::getDefaultSender()->getEmail();
    $notification_type = '';

    if (AngieApplication::isOnDemand()) {
        $hostname = 'activecollab.com';
    } else {
        $hostname = 'localhost.localdomain';
        if (isset($_SERVER) and array_key_exists('SERVER_NAME', $_SERVER) and !empty($_SERVER['SERVER_NAME'])) {
            $hostname = $_SERVER['SERVER_NAME'];
        } elseif (function_exists('gethostname') && gethostname() !== false) {
            $hostname = gethostname();
        } elseif (php_uname('n') !== false) {
            $hostname = php_uname('n');
        }
    }

    // @TODO refactor this - extract to a function?
    /* ----------------- ROUTE EMAIL ----------------- */
    if (!empty($references)) {
        foreach ($references as $reference) {
            if (strpos($reference, $hostname) !== false) {
                $notification_type = 'mail_to_comment';
                break;
            }
        }
    } elseif ($incoming_mail_message->getSender() == $default_sender) {
        if ($incoming_mail_message->getMailer() == EmailReplyExtractor::ANDROID_MAIL) {
            $bounce = "The Android Email application isn't supported. Your reply hasn't been posted as a comment. Please use Gmail or a similar app instead.";

            return;
        }
    }

    $project_hash = '';
    $parts = explode('@', $default_sender);

    foreach ($recipients as $key => $recipient) {
        preg_match('/\+(.+)\@/', $recipient, $matches);
        if (
            !empty($matches)
            && str_starts_with($recipient, $parts[0])
            && str_ends_with($recipient, $parts[1])
        ) {
            $notification_type = 'mail_to_project';
            $project_hash = $matches[1];
            /*
             Lets throw away this address as we now know which project should we try to load.
             Other recipients are potential subscribers.
            */
            unset($recipients[$key]);
            break; // no need to further iterate through recipients
        }
    }
    /* ----------------- END ROUTE EMAIL ----------------- */

    if ($notification_type !== 'mail_to_project') {
        return; // No need to deal with this one any further.
    }

    AngieApplication::log()->info(
        'Email import: Email should be imported as a task or discussion',
        [
            'email_source' => $source,
            'project_hash' => $project_hash,
        ]
    );

    // load project based on the hash found in the email address among recipients

    /** @var Project $project */
    $project = $project_hash
        ? Projects::findOneBySql('SELECT * FROM projects WHERE project_hash = ?', $project_hash)
        : null;

    if (empty($project)) {
        $bounce = sprintf(
            "The email you sent hasn't been posted to %s. It's possible that the project has been deleted or you used an incorrect address. Please contact the system administrator to check what went wrong.",
            AngieApplication::getName()
        );

        return; // No project? No need to deal with this any further.
    }

    // load user object from sender email
    $user = Users::findByEmail($incoming_mail_message->getSender());

    if (empty($user)) {
        $bounce = sprintf(
            "The email you sent hasn't been imported. You need to have an account in %s. Please contact the system administrator to enable this for you.",
            AngieApplication::getName()
        );

        return; // Sender is not in our system? No need to deal with this any further
    }

    $can_add_tasks = Tasks::canAdd($user, $project);
    $can_add_discussions = Discussions::canAdd($user, $project);

    if (!$can_add_tasks && !$can_add_discussions) {
        $bounce = "The email you sent hasn't been imported. You need the right permissions to be able to do this. Please contact the system administrator to enable this for you.";

        return; // Sender can not add a task or a discussion to the project? No need to deal with this any further
    }

    try {
        $context_name = $incoming_mail_message->getSubject();
        $context_body = $incoming_mail_message->getBody();

        if (mb_strlen($context_name) > 150) {
            $context_name = strtok(wordwrap($context_name, 149, "\n"), "\n") . '…';
            $context_body = $incoming_mail_message->getSubject() . '<br> <br>' . $incoming_mail_message->getBody();
        }

        if ($can_add_discussions && !$can_add_tasks) {
            $context = new Discussion();
            $notify_subscribers_about = 'discussions/new_discussion';
        } elseif ($can_add_tasks) {
            $context = new Task();
            $notify_subscribers_about = 'tasks/new_task';
        } else {
            throw new LogicException('User does not have permissions to add discussions or tasks.');
        }

        $context->setProject($project);

        if ($context instanceof Task) {
            $context->setTaskList(TaskLists::getFirstTaskList($project));
        }

        $context->setName($context_name);
        $context->setBody(nl2br($context_body));
        $context->setCreatedBy($user);
        $context->setAttribute('attach_uploaded_files', $incoming_mail_message->getAttachments());
        $context->save();

        // subscribe recipients if they can be subscribed to the given object
        foreach ($recipients as $recipient) {
            if (($subscriber = Users::findByEmail($recipient)) && $context->canSubscribe($subscriber)) {
                $context->subscribe($subscriber);
            }
        }

        $project_leader = $project->getLeader();

        if ($project_leader && !in_array($project_leader->getEmail(), $recipients) && $user->isClient()) {
            $context->subscribe($project_leader);
        }

        if ($context instanceof Task) {
            DataObjectPool::announce(new TaskCreatedEvent($context));
        } else {
            DataObjectPool::announce($context, DataObjectPool::OBJECT_CREATED, []);
        }

        AngieApplication::log()->event(
            'task_created_from_email',
            'Email import: Message has been imported as {object} #{object_id}',
            [
                'email_source' => $source,
                'object' => $context->getVerboseType(true),
                'object_id' => $context->getId(),
                'subscribers' => array_map(function ($s) {
                    return $s instanceof IUser ? $s->getEmail() : 'not a user';
                }, $context->getSubscribers()),
            ]
        );

        // notify subscribes
        AngieApplication::notifications()
            ->notifyAbout($notify_subscribers_about, $context, $context->getCreatedBy())
            ->sendToSubscribers();
    } catch (Exception $e) {
        $bounce = 'Error: ' . $e->getMessage();
    }
}
