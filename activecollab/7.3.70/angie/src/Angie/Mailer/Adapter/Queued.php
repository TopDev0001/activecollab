<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Mailer\Adapter;

use ActiveCollab\ActiveCollabJobs\Jobs\Smtp\SendMessage;
use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use ActiveCollab\Foundation\Mail\MailRouterInterface;
use ActiveCollab\JobsQueue\Jobs\Job;
use Angie\Mailer;
use AngieApplication;
use DataObject;
use EmailIntegration;
use IFile;
use Integrations;
use IUser;
use OnDemand;

final class Queued extends Adapter
{
    public function send(
        IUser $sender,
        IUser $recipient,
        string $subject,
        string $body,
        DataObject $context = null,
        iterable $attachments = null,
        callable $on_sent = null
    ): int
    {
        $data = [
            'instance_id_in_reply_to' => AngieApplication::isOnDemand(),
        ];

        if (AngieApplication::isOnDemand()) {
            $data['route'] = AngieApplication::getContainer()
                ->get(MailRouterInterface::class)
                    ->getMailRoute();
        } else {
            $data['require_smtp_connection_data'] = true;

            /** @var EmailIntegration $email_integration */
            $email_integration = Integrations::findFirstByType(EmailIntegration::class);

            $data['smtp_host'] = $email_integration->getSmtpHost();
            $data['smtp_port'] = $email_integration->getSmtpPort();
            $data['smtp_security'] = $email_integration->getSmtpSecurity();
            $data['smtp_username'] = $email_integration->getSmtpUsername();
            $data['smtp_password'] = $email_integration->getSmtpPassword();
            $data['smtp_verify_certificate'] = $email_integration->getSmtpVerifyCertificate();

            if (empty($data['smtp_security'])) {
                $data['smtp_security'] = 'auto';
            }

            $in_test = AngieApplication::getContainer()
                ->get(ApplicationModeInterface::class)
                    ->isInTestMode();

            // If SMTP is not set, and we are not in test, skip email sending
            if ((empty($data['smtp_host']) || empty($data['smtp_port']) || empty($data['smtp_security'])) && !$in_test) {
                AngieApplication::log()->notice(
                    'Skipped sending email to {recipient_email}. Outgoing mail server not configured',
                    [
                        'subject' => $subject,
                        'recipient_email' => $recipient->getEmail(),
                    ]
                );

                return 0;
            }
        }

        $default_sender = Mailer::getDefaultSender();

        $data = array_merge(
            $data,
            [
                'priority' => Job::HAS_HIGHEST_PRIORITY,
                'instance_id' => AngieApplication::getAccountId(),
                'attempts' => 5,
                'delay' => 60,
                'first_attempt_delay' => 0,
                'from' => [
                    'name' => $this->getFrom($sender, $default_sender),
                    'email' => $default_sender->getEmail(),
                ],
                'to' => [
                    'name' => $recipient->getName(),
                    'email' => $recipient->getEmail(),
                ],
                'subject' => $subject,
                'body' => $body,
                'route_reply_to' => $this->routeReplyTo($sender, $recipient, $context),
                'service_address' => $default_sender->getEmail(),
                'message_id' => $this->getMessageId(),
                'entity_ref_id' => $this->getEntityRefId($context),
            ]
        );

        if (is_array($attachments) && !empty($attachments)) {
            $data['attachments'] = [];

            foreach ($attachments as $path => $attachment) {
                if ($attachment instanceof IFile) {
                    $data['attachments'][] = [
                        'path' => $attachment->getPath(),
                        'name' => $attachment->getName(),
                    ];
                } else {
                    $data['attachments'][] = [
                        'path' => $path,
                        'name' => $attachment,
                    ];
                }
            }
        }

        if (AngieApplication::isOnDemand()) {
            OnDemand::prepareSendMessageJobData($data);
        }

        AngieApplication::jobs()->dispatch(
            new SendMessage($data),
            EmailIntegration::JOBS_QUEUE_CHANNEL
        );

        return $this->messageSent(
            $sender,
            $recipient,
            $subject,
            $body,
            $context,
            $attachments,
            $on_sent
        );
    }

    private function getFrom(IUser $sender, IUser $default_sender): string
    {
        return $sender->getEmail() == $default_sender->getEmail()
            ? $sender->getDisplayName()
            : $sender->getDisplayName() . ' (' . AngieApplication::getName() . ')';
    }

    private function getMessageId(): string
    {
        if (AngieApplication::isOnDemand() && extension_loaded('openssl')) {
            return sprintf(
                '<%s-%s@activecollab.email>',
                base_convert(
                    (string) $this->getMicrotime(),
                    10,
                    36
                ),
                base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36)
            );
        }

        return '';
    }

    private function getMicrotime(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function getEntityRefId(DataObject $context = null): string
    {
        if ($context instanceof DataObject) {
            return implode(
                '-',
                [
                    AngieApplication::getAccountId(),
                    $context->getModelName(true, true),
                    $context->getId(),
                ]
            );
        }

        return '';
    }
}
