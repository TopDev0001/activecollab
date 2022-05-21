<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor;

use ActiveCollab\EmailReplyExtractor;
use ActiveCollab\Foundation\Mail\Incoming\Message\Message;
use ActiveCollab\Foundation\Mail\Incoming\Message\MessageInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Address\Address;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Address\AddressInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Bouncer\BouncerInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet\Resolver\MailboxesSetResolverInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\MiddlewareInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\BounceInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\NotForUsBounce;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Capture\CaptureInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\MiddlewareResultInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Skip\SkipInterface;
use AngieApplication;
use EmailMessageInterface;
use Psr\Log\LoggerInterface;
use UploadedFile;
use UploadedFiles;

class Processor implements ProcessorInterface
{
    private MailboxesSetResolverInterface $mailboxes_set_resolver;
    private BouncerInterface $bouncer;
    private LoggerInterface $logger;
    private array $middlewares;

    public function __construct(
        MailboxesSetResolverInterface $mailboxes_set_resolver,
        BouncerInterface $bouncer,
        LoggerInterface $logger,
        MiddlewareInterface ...$middlewares
    )
    {
        $this->mailboxes_set_resolver = $mailboxes_set_resolver;
        $this->bouncer = $bouncer;
        $this->logger = $logger;
        $this->middlewares = $middlewares;
    }

    public function processEmailMessage(
        EmailMessageInterface $message,
        string $source
    ): ?MiddlewareResultInterface
    {
        [
            $body,
            $mailer,
        ] = EmailReplyExtractor::extractReply((array) $message->getHeaders(), $message->getBody());

        $attachments = $this->processAttachments($message, $message->getAttachments());

        foreach ($attachments as $attachment_id => $attachment) {
            if (ctype_digit($attachment_id)) {
                continue;
            }

            $image_tag = sprintf('[Image: cid:%s]', $attachment_id);

            if (mb_strpos($body, $image_tag) !== false) {
                $body = mb_str_replace(
                    $image_tag,
                    sprintf(
                        '<img src="%s" alt="%s" image-type="attachment" object-id="%s" />',
                        $attachment->getThumbnailUrl(),
                        $attachment->getName(),
                        $attachment->getCode()
                    ),
                    $body
                );

                unset($attachments[$attachment_id]);
            }
        }

        $sender = $message->getSenders()[0];

        return $this->process(
            new Message(
                $sender,
                $message->getRecipients(),
                $message->getSubject(),
                $body,
                $message->getReferences(),
                $attachments,
                $mailer
            ),
            $source
        );
    }

    public function getMatchingRecipient(MessageInterface $message): ?AddressInterface
    {
        $mailboxes_set = $this->mailboxes_set_resolver->resolveMailboxesSet();

        foreach ($mailboxes_set->getMailboxes() as $mailbox_address) {
            $parts = explode('@', $mailbox_address);

            foreach ($message->getRecipients() as $recipient) {
                if ($recipient === $mailbox_address) {
                    return new Address($mailbox_address);
                }

                preg_match('/\+(.+)@/', $recipient, $matches);

                if (!empty($matches)
                    && str_starts_with($recipient, $parts[0])
                    && str_ends_with($recipient, $parts[1])
                ) {
                    return new Address($mailbox_address, trim($matches[1]));
                }
            }
        }

        return null;
    }

    public function process(MessageInterface $message, string $source): ?MiddlewareResultInterface
    {
        $this->logInfo(
            'Begin message processing.',
            [
                'subject' => $message->getSubject(),
                'from' => $message->getSender(),
                'recipients' => $message->getRecipients(),
            ]
        );

        $matching_recipient = $this->getMatchingRecipient($message);

        if (empty($matching_recipient)) {
            return $this->noMatchingRecipientBounce($message, $source);
        }

        foreach ($this->middlewares as $middleware) {
            $result = $middleware->process($message, $matching_recipient, $source);

            if ($result instanceof BounceInterface) {
                $this->bouncer->bounce($message, $result);

                return $result;
            }

            if ($result instanceof CaptureInterface) {
                return $result;
            }

            if ($result instanceof SkipInterface) {
                $this->logInfo(
                    'Middleware {middleware} skipped "{subject}" message sent to {matching_recipient}. Reason: {reason}.',
                    [
                        'subject' => $message->getSubject(),
                        'from' => $message->getSender(),
                        'matching_recipient' => $matching_recipient->getFullAddress(),
                        'reason' => $result->getReason(),
                        'email_source' => $source,
                    ]
                );
            }
        }

        $this->logInfo(
            'Message {subject} sent to to {matching_recipient} was not handled by any of the middlewares.',
            [
                'subject' => $message->getSubject(),
                'from' => $message->getSender(),
                'matching_recipient' => $matching_recipient->getFullAddress(),
                'email_source' => $source,
            ]
        );

        $this->bouncer->bounce(
            $message,
            new NotForUsBounce('This message was not recognized as a reply or as a new task and could not be imported.')
        );

        return null;
    }

    /**
     * @return UploadedFile[]
     */
    private function processAttachments(EmailMessageInterface $message, array $attachments = []): array
    {
        if (!empty($attachments)) {
            $this->logInfo(
                'Prepraing to extract attachments from message.',
                [
                    'subject' => $message->getSubject(),
                    'attachments' => $attachments,
                ]
            );
        }

        do {
            $filepath = AngieApplication::getAvailableWorkFileName('mail_attachment');
        } while (is_file($filepath));

        $attachment_files = [];
        $attachment_files_for_log = [];

        foreach ($attachments as $attachment) {
            if (file_put_contents($filepath, base64_decode($this->escapePythonBytesEncode($attachment['base64'])))) {
                $uploaded_file = UploadedFiles::addFile(
                    $filepath,
                    $attachment['filename'],
                    $attachment['mime_type']
                );

                if ($attachment['content_disposition'] === 'inline' && !empty($attachment['attachment_id'])) {
                    $attachment_files[$attachment['attachment_id']] = $uploaded_file;

                    $attachment_files_for_log[] = sprintf(
                        '%s (CID: %s)',
                        $uploaded_file->getName(),
                        $attachment['attachment_id']
                    );
                } else {
                    $attachment_files[] = $uploaded_file;

                    $attachment_files_for_log[] = $uploaded_file->getName();
                }

                @unlink($filepath);
            }
        }

        if (!empty($attachment_files_for_log)) {
            $this->logInfo(
                'Attachments extracted from message.',
                [
                    'subject' => $message->getSubject(),
                    'attachments' => $attachment_files_for_log,
                ]
            );
        }

        return $attachment_files;
    }

    private function escapePythonBytesEncode(string $attachment_base64): string
    {
        if (strpos($attachment_base64, "b'") === 0) {
            return mb_substr($attachment_base64, 2);
        }

        return $attachment_base64;
    }

    private function noMatchingRecipientBounce(MessageInterface $message, string $source): BounceInterface
    {
        $mailboxes = $this->mailboxes_set_resolver->resolveMailboxesSet()->getMailboxes();

        $this->logInfo(
            'This message is not for us, {default_sender} not found in the list of recipients',
            [
                'email_source' => $source,
                'mailboxes' => $mailboxes,
            ]
        );

        $bounce = new NotForUsBounce(
            sprintf(
                'This message is not for us, %s not found in the list of recipients.',
                implode(', ', $mailboxes)
            )
        );

        $this->bouncer->bounce($message, $bounce);

        return $bounce;
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info(
            $this->prepareLogMessage($message),
            $this->prepareLogContext($context)
        );
    }

    private function prepareLogMessage(string $message): string
    {
        return sprintf('Email Import: %s', $message);
    }

    private function prepareLogContext(array $context): array
    {
        return $context;
    }
}
