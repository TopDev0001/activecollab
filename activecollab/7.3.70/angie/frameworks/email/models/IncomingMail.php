<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\EmailReplyExtractor;
use Angie\Events;
use Fetch\Attachment;
use Fetch\Message;
use Fetch\Server;

final class IncomingMail
{
    public static function checkImap()
    {
        /*
         * ---------------------------------------------------------
         * SET THE SCRIPT EXECUTION LIMIT
         * ---------------------------------------------------------
         */
        $max_execution_time = defined('MAX_JOBS_EXECUTION_TIME') && MAX_JOBS_EXECUTION_TIME ? MAX_JOBS_EXECUTION_TIME : 60;
        $work_until = time() + $max_execution_time; // Assume that we spent 1 second bootstrapping the command

        AngieApplication::log()->info(
            'Preparing to check IMAP server',
            [
                'max_exec_time' => $max_execution_time,
            ]
        );

        /** @var $email_integration EmailIntegration */
        $email_integration = Integrations::findOneBy('type', EmailIntegration::class);

        $server = new Server($email_integration->getImapHost(), $email_integration->getImapPort());
        $server->setAuthentication($email_integration->getImapUsername(), $email_integration->getImapPassword());

        if (!$email_integration->getImapVerifyCertificate()) {
            $server->setFlag('novalidate-cert', true);
        }

        if ($email_integration->getImapSecurity() == 'none') {
            $server->setFlag('notls');
        }

        self::processMessage($email_integration->getImapHost(), $server, $work_until);
    }

    /**
     * Connects to local eml file as mailbox and imports the message as it was from real imap server.
     *
     * @param string $work_path
     * @param string $filename
     * @param bool   $remove_file_when_done
     */
    public static function importFromFile($work_path, $filename, $remove_file_when_done = true, callable $output = null)
    {
        $filepath = "$work_path/$filename";

        if (is_file($filepath)) {
            if ($output) {
                $output("Starting server from file '$filepath'");
            }

            $server = new FileMailbox($filename, '');
            self::processMessage($filepath, $server, time());

            if ($remove_file_when_done) {
                if (unlink($filepath)) {
                    AngieApplication::log()->info('Email import: file {file_path} has been removed', ['source' => $filepath]);

                    if ($output) {
                        $output("File '$filepath' has been removed");
                    }
                } else {
                    AngieApplication::log()->error('Email import: failed to remove {file_path} file', ['source' => $filepath]);

                    if ($output) {
                        $output("Failed to remove '$filepath' file");
                    }
                }
            }
        } else {
            AngieApplication::log()->error('Email import: file {file_path} not found', ['source' => $filepath]);

            if ($output) {
                $output("File '$filepath' not found");
            }
        }
    }

    /**
     * Get Recipients from CC nad TO segment.
     *
     * @return array
     */
    private static function getRecipients(Message $message)
    {
        $recipients = [];

        if ($message->getAddresses('cc')) {
            if (is_array($message->getAddresses('cc'))) {
                foreach ($message->getAddresses('cc') as $addr) {
                    $recipients[] = $addr['address'];
                }
            }
        }

        if ($message->getAddresses('to')) {
            if (is_array($message->getAddresses('to'))) {
                foreach ($message->getAddresses('to') as $addr) {
                    $recipients[] = $addr['address'];
                }
            }
        }

        // Add delivered-to email to recipients.
        // This is used when forwarding to project is enabled
        if (preg_match('/Delivered-To:(.+)/', $message->getRawHeaders(), $matches)) {
            $delivered_to = trim($matches[1]);
            if (!in_array($delivered_to, $recipients)) {
                $recipients[] = $delivered_to;
            }
        }

        // Add x-forwarded-to email to recipients.
        // This is also used when forwarding to project is enabled
        if (preg_match('/X-Forwarded-To:(.+)/', $message->getRawHeaders(), $matches)) {
            $forwarded_to = trim($matches[1]);
            if (!in_array($forwarded_to, $recipients)) {
                $recipients[] = $forwarded_to;
            }
        }

        return $recipients;
    }

    /**
     * Get message references.
     *
     * @return array
     */
    private static function getReferences(Fetch\Message $message)
    {
        $references = !empty($message->getOverview()->references) ? explode(' ', $message->getOverview()->references) : [];

        if (!empty($message->getOverview()->in_reply_to) && !in_array($message->getOverview()->in_reply_to, $references)) {
            $references[] = $message->getOverview()->in_reply_to;
        }

        return array_filter($references);
    }

    /**
     * Check to see if email is auto responder or failed delivery.
     *
     * @param  Message $message
     * @return bool
     */
    private static function checkIsAutomaticEmail($message)
    {
        $headers = explode("\n", $message->getRawHeaders());

        $auto_submitted_response = [
            'auto-generated',
            'auto-replied',
            'auto-notified',
        ];

        foreach ($headers as $header) {
            if (preg_match('/^(Auto-Submitted[^:]*): (.*)/is', $header, $results)) {
                if (isset($results[1]) && isset($results[2])) {
                    if (in_array(trim($results[2]), $auto_submitted_response)) {
                        AngieApplication::log()->info(
                            'Incoming message marked as not comming from a human because it contains Auto-Submitted header with {auto_submitted_header_value} value.',
                            [
                                'auto_submitted_header_value' => $results[2],
                            ]
                        );

                        return true;
                    }
                }
            }

            if (preg_match('/^(Return-Path[^:]*): (.*)/is', $header, $results)) {
                if (isset($results[1]) && isset($results[2])) {
                    if (trim($results[2]) == '<>') {
                        AngieApplication::log()->info(
                            'Incoming message marked as not comming from a human because it has empty return path.'
                        );

                        return true;
                    }
                }
            }

            if (preg_match('/^(Precedence[^:]*): (.*)/is', $header, $results)) {
                if (isset($results[1]) && isset($results[2])) {
                    if (trim($results[2]) == 'bulk' || trim($results[2]) == 'junk') {
                        AngieApplication::log()->info(
                            'Incoming message marked as not comming from a human because it contains Precedence header with {precedence_header_value} value.',
                            [
                                'precedence_header_value' => $results[2],
                            ]
                        );

                        return true;
                    }
                }
            }

            if (preg_match('/^(X-Failed-Recipients[^:]*): (.*)/is', $header, $results)) {
                if (isset($results[1])) {
                    AngieApplication::log()->info(
                        'Incoming message marked  as not comming from a human because it is recognized as bounce for {failed_recipient} recipient.',
                        [
                            'failed_recipient' => $results[2],
                        ]
                    );

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Process messages from serer.
     *
     * @param string $source
     * @param Server $server
     * @param int    $work_until
     */
    private static function processMessage($source, $server, $work_until)
    {
        do {
            $ordered_messages = $server->getOrderedMessages(1, true, 1);

            $message = reset($ordered_messages);

            if (empty($message)) {
                AngieApplication::log()->error(
                    'Email import: no message to import',
                    [
                        'source' => $source,
                    ]
                );

                return;
            }

            // ignore messages from autoresponders or failed delivery
            if (self::checkIsAutomaticEmail($message)) {
                AngieApplication::log()->notice(
                    'Email import: Skipping a message that was sent by an automated system',
                    [
                        'source' => $source,
                    ]
                );

                $message->delete();
                $server->expunge();

                continue;
            }

            if (self::checkIfForwardingVerification($message)) {
                AngieApplication::log()->notice(
                    'Email import: Forwarding verification email, sending notification to owners',
                    [
                        'source' => $source,
                    ]
                );

                self::notifyOwnersOnForwardingVerification($message);

                $message->delete();
                $server->expunge();

                continue;
            }

            if ($message->getHtmlBody()) {
                $body = $message->getHtmlBody();
            } else {
                $body = $message->getPlainTextBody();
            }

            [
                $body,
                $mailer,
            ] = EmailReplyExtractor::extractReply((array) $message->getHeaders(), $body);

            $attachment_files = [];
            if ($attachments = $message->getAttachments()) {
                $attachment_files = self::processAttachments($attachments);
            }
            $sender = $message->getAddresses('sender')['address'];

            $incoming_mail_message = new IncomingMailMessage(
                $sender,
                self::getRecipients($message),
                $message->getSubject(),
                $body,
                self::getReferences($message),
                $attachment_files,
                $mailer
            );

            // mark the message for deletion
            $message->delete();

            AngieApplication::log()->info(
                'Email import: Message ready for routing',
                [
                    'source' => $source,
                    'references' => $incoming_mail_message->getReferences(),
                    'sender' => $incoming_mail_message->getSender(),
                    'recipients' => $incoming_mail_message->getRecipients(),
                    'subject' => $incoming_mail_message->getSubject(),
                    'body' => $incoming_mail_message->getBody(),
                    'attachments' => is_array($attachments) ? array_map(function ($attachment) {
                        return $attachment instanceof UploadedFile ? $attachment->getName() : 'not an uploaded file';
                    }, $attachments) : [],
                ]
            );

            $bounce = '';
            Events::trigger(
                'on_email_received',
                [
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
                    ->sendToUsers(
                        [
                            new AnonymousUser(null, $sender),
                        ]
                    );
            } else {
                AngieApplication::log()->info(
                    'Email import: Message processed successfully',
                    [
                        'source' => $source,
                    ]
                );
            }

            $server->expunge();
        } while (time() < $work_until);
    }

    /**
     * Handle message attachments.
     *
     * @param  Attachment[] $attachments
     * @return array
     */
    private static function processAttachments($attachments)
    {
        // create new unique filename and filepath
        do {
            $filepath = AngieApplication::getAvailableWorkFileName('mail_attachment');
        } while (is_file($filepath));

        $attachment_files = [];
        foreach ($attachments as $attachment) {
            if (file_put_contents($filepath, $attachment->getData())) {
                $attachment_files[] = UploadedFiles::addFile($filepath, $attachment->getFileName(), $attachment->getMimeType())->getCode();
                @unlink($filepath);
            }
        }

        return $attachment_files;
    }

    /**
     * Handle forwarding verification.
     *
     * @param Message $message
     */
    private static function notifyOwnersOnForwardingVerification($message)
    {
        // Add these messages to lang directionary:

        // lang('Email Forwarding Configuration')
        // lang('We have received an email that looks like a forwarding configuration email. Please take a look at the email content below and follow the instructions.')

        if ($owners = Users::findByType([Owner::class])) {
            /** @var NotifyOwnersNotification $notification */
            $notification = AngieApplication::notifications()->notifyAbout('system/notify_owners');
            $notification
                ->setMessage('We have received an email that looks like a forwarding configuration email. Please take a look at the email content below and follow the instructions.')
                ->setSubject('Email Forwarding Configuration')
                ->setAdditionalPayload($message->getPlainTextBody())
                ->sendToUsers($owners);

            AngieApplication::log()->notice(
                'Forwarded email forwarding verification email to {forwarded_to}.',
                [
                    'confirmation_from' => $message->getAddresses('sender')['address'],
                    'confirmation_body' => $message->getPlainTextBody(),
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

    /**
     * Check if email is forwarding verification.
     *
     * @param  Message $message
     * @return bool
     */
    private static function checkIfForwardingVerification($message)
    {
        return in_array(
            $message->getAddresses('sender')['address'],
            [
                'forwarding-noreply@google.com',
            ]
        );
    }
}
