<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class EmailMessage implements EmailMessageInterface
{
    const GMAIL_FORWARDING_ADDRESS = 'forwarding-noreply@google.com';

    const AUTO_SUBMITTED_RESPONSE_VALUES = [
        'auto-generated',
        'auto-replied',
        'auto-notified',
    ];

    const PRECEDENCE_VALUES = [
        'bulk',
        'junk',
    ];

    private array $headers;
    private array $senders;
    private array $recipients;
    private string $body;
    private string $subject;
    private array $attachments;

    public function __construct(
        array $headers = [],
        array $senders = [],
        array $recipients = [],
        string $body = '',
        string $subject = '',
        array $attachments = []
    ) {
        $this->headers = $headers;
        $this->senders = $senders;
        $this->recipients = $recipients;
        $this->body = $body;
        $this->subject = $subject;
        $this->attachments = $attachments;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getSenders(): array
    {
        return $this->senders;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getReferences(): array
    {
        $references = [];

        if (isset($this->headers['message-id'])) {
            if (!in_array($this->headers['message-id'], $references, true)) {
                $references[] = $this->headers['message-id'];
            }
        }

        if (isset($this->headers['references'])) {
            if (!in_array($this->headers['references'], $references, true)) {
                $references[] = $this->headers['references'];
            }
        }

        if (isset($this->headers['in-reply-to'])) {
            if (!in_array($this->headers['in-reply-to'], $references, true)) {
                $references[] = $this->headers['in-reply-to'];
            }
        }

        return array_unique($references);
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function isForwardingNotification(): bool
    {
        return array_search(self::GMAIL_FORWARDING_ADDRESS, $this->getHeaders()) !== false;
    }

    public function isFromAutoResponder(): bool
    {
        foreach ($this->headers as $header => $value) {
            if ($header === 'auto-submitted' && in_array($value, self::AUTO_SUBMITTED_RESPONSE_VALUES, true)) {
                return true;
            } elseif ($header === 'return-path' && $value === '<>') {
                return true;
            } elseif ($header === 'precedence' && in_array($value, self::PRECEDENCE_VALUES, true)) {
                return true;
            } elseif ($header === 'x-failed-recipients' || $value === 'x-failed-recipients') {
                return true;
            }
        }

        return false;
    }
}
