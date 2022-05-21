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
use AngieApplication;
use ApplicationObject;
use Exception;
use IComments;
use UploadedFile;

class MailToCommentMiddleware extends Middleware
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

        if (!$this->isMailToComment($matched_recipient)) {
            return new Skip('Not a reply to comment.');
        }

        $this->logInfo(
            'Email should be imported as a comment.',
            [
                'email_source' => $source,
            ]
        );

        // @TODO subscribe recipients to the parent?
        // remove the default sender from recipients, so others will potentially become subscribers
        // unset($recipients[$key]);

        // find the context
        $context = $this->getReplyContext($message);

        if (!$context instanceof IComments) {
            return new UnknownIncomingMailContextBounce(
                "It's not possible to send a reply to this type of notification."
            );
        }

        $user = $this->getSenderUser($message);

        if (empty($user) || !$context->canComment($user)) {
            return new IncomingMailContextPermissionsBounce(
                lang("Your reply hasn't been posted as a comment. It's possible that this item is in trash or you don't have permission to access it.")
            );
        }

        try {
            $comment = $context->submitComment(
                nl2br($message->getBody()),
                $user,
                [
                    'attach_uploaded_files' => array_map(
                        function (UploadedFile $attachment) {
                            return $attachment->getCode();
                        },
                        $message->getAttachments()
                    ),
                ],
                true
            );

            $this->logInfo(
                'Message has been imported as comment #{comment_id} to {parent_type} #{parent_id}',
                [
                    'event' => 'comment_created_from_email',
                    'email_source' => $source,
                    'parent_type' => $context->getVerboseType(true),
                    'parent_id' => $context->getId(),
                    'comment_id' => $comment->getId(),
                ]
            );

            return new Capture();
        } catch (Exception $e) {
            $this->logInfo(
                'Failed to post comment based on incoming email.',
                [
                    'exception' => $e,
                ]
            );

            return new OperationFailedBounce('Email import operation failed.');
        }
    }

    private function isMailToComment(AddressInterface $matched_recipient): bool
    {
        return empty($matched_recipient->getTag());
    }

    /**
     * @return ApplicationObject|IComments|null
     */
    private function getReplyContext(MessageInterface $message): ?IComments
    {
        $trimmed_references = $message->getTrimmedReferences();
        $context = !empty($trimmed_references)
            ? AngieApplication::jobsConnection()->executeFirstRow(
                'SELECT parent_type, parent_id FROM email_log WHERE message_id IN ?',
                $trimmed_references
            )
            : null;

        if ($context === null || empty($context['parent_type'])) {
            $this->logWarning(
                'Email import: Failed to find outgoing message with any of the referecens.',
                [
                    'references' => $trimmed_references,
                ]
            );

            return null;
        }

        $context = $this->data_object_pool->get(
            $context['parent_type'],
            $context['parent_id']
        );

        if (!$context instanceof IComments) {
            $this->logWarning(
                'Email import: Context {parent_type} #{parent_id} not found or does not accept comments.',
                [
                    'parent_type' => $context['parent_type'],
                    'parent_id' => $context['parent_id'],
                ]
            );

            return null;
        }

        return $context;
    }
}
