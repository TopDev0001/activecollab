<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\EmailReplyExtractor;
use ActiveCollab\Logger\LoggerInterface;
use Angie\Events;

class OnDemandEmailImporter implements EmailImporterInterface
{
    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(LoggerInterface $log) {
        $this->log = $log;
    }

    public function import(EmailMessageInterface $message)
    {
        $source = 'import_email.py';

        if ($message->isFromAutoResponder()) {
            $this->log->notice('Email import: Skipping an auto-responder message');

            return;
        }

        if ($message->isForwardingNotification()) {
            $this->log->notice('Email import: Forwarding verification email, sending notification to owners');

            $this->notifyOwnersOnForwardingVerification($message);

            return;
        }

        [
            $body,
            $mailer,
        ] = EmailReplyExtractor::extractReply((array) $message->getHeaders(), $message->getBody());

        $attachment_files = $this->processAttachments($message->getAttachments());
        $sender = $message->getSenders()[0];

        $incoming_mail_message = new IncomingMailMessage(
            $sender,
            $message->getRecipients(),
            $message->getSubject(),
            $body,
            $message->getReferences(),
            $attachment_files,
            $mailer
        );

        AngieApplication::log()->info(
            'Email import: Message ready for routing',
            [
                'references' => $incoming_mail_message->getReferences(),
                'sender' => $incoming_mail_message->getSender(),
                'recipients' => $incoming_mail_message->getRecipients(),
                'subject' => $incoming_mail_message->getSubject(),
                'body' => $incoming_mail_message->getBody(),
                'attachments' => is_array($attachment_files) ? array_map(function ($attachment) {
                    return $attachment instanceof UploadedFile ? $attachment->getName() : 'not an uploaded file';
                }, $attachment_files) : [],
            ]
        );

        $bounce = '';
        Events::trigger(
            'on_email_received', [
                $incoming_mail_message,
                $source,
                &$bounce,
            ]
        );

        if (!empty($bounce)) {
            AngieApplication::log()->info(
                'Email import: Message bounced',
                [
                    'source' => $source,
                    'reason' => $bounce,
                ]
            );

            /** @var BounceEmailNotification $notification */
            $notification = AngieApplication::notifications()->notifyAbout('system/bounce_email');
            $notification
                ->setBounceReason($bounce)
                ->sendToUsers([new AnonymousUser(null, $sender)]);
        } else {
            AngieApplication::log()->info(
                'Email import: Message processed successfully',
                [
                    'source' => $source,
                ]
            );
        }
    }

    private static function notifyOwnersOnForwardingVerification(EmailMessageInterface $message)
    {
        if ($owners = Users::findByType([Owner::class])) {
            /** @var NotifyOwnersNotification $notification */
            $notification = AngieApplication::notifications()->notifyAbout('system/notify_owners');
            $notification
                ->setMessage('We have received an email that looks like a forwarding configuration email. Please take a look at the email content below and follow the instructions.')
                ->setSubject('Email Forwarding Configuration')
                ->setAdditionalPayload($message->getBody())
                ->sendToUsers($owners);

            AngieApplication::log()->notice(
                'Forwarded email forwarding verification email to {forwarded_to}.',
                [
                    'confirmation_from' => implode(', ', $message->getSenders()),
                    'confirmation_body' => $message->getBody(),
                    'forwarded_to' => implode(', ', array_map(function (User $owner) {
                        return $owner->getEmail();
                    }, $owners)),
                ]
            );
        } else {
            AngieApplication::log()->critical(
                'Failed to forward email forwarding verification email. No owners found.'
            );
        }
    }

    private function processAttachments(array $attachments = []): array
    {
        do {
            $filepath = AngieApplication::getAvailableWorkFileName('mail_attachment');
        } while (is_file($filepath));

        $attachment_files = [];

        foreach ($attachments as $attachment) {
            if (file_put_contents($filepath, base64_decode($this->escapePythonBytesEncode($attachment['base64'])))) {
                $attachment_files[] = UploadedFiles::addFile(
                    $filepath,
                    $attachment['filename'],
                    $attachment['mime_type']
                )->getCode();

                @unlink($filepath);
            }
        }

        return $attachment_files;
    }

    private function escapePythonBytesEncode($attachment_base64)
    {
        if (strpos($attachment_base64, "b'") === 0) {
            return mb_substr($attachment_base64, 2);
        }

        return $attachment_base64;
    }
}
