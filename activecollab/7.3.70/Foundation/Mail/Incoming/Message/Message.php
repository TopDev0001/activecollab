<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Message;

class Message implements MessageInterface
{
    private string $sender;
    private array $recipients;
    private string $subject;
    private string $body;
    private array $attachments;
    private array $references;
    private string $mailer;

    public function __construct(
        string $sender,
        array $recipients,
        string $subject,
        string $body,
        array $references,
        array $attachments,
        string $mailer
    )
    {
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
        $this->references = $references;
        $this->mailer = $mailer;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    public function getTrimmedReferences(): array
    {
        $result = [];

        foreach ($this->references as $reference_to_search) {
            $result[] = rtrim(ltrim($reference_to_search, '<'), '>');
        }

        return $result;
    }

    public function getMailer(): string
    {
        return $this->mailer;
    }
}
