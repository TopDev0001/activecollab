<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class JsonEmailProcessor implements EmailProcessorInterface
{
    const RECIPIENT_HEADERS = [
        'to',
        'cc',
        'bcc',
        'delivered-to',
        'x-forwarded-to',
    ];

    private array $raw_decoded_message;

    public function __construct(string $source)
    {
        $this->raw_decoded_message = $this->validateJson($source);
    }

    private function validateJson(string $source): array
    {
        $result = json_decode($source, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Failed to parse JSON source: %s.',
                    json_last_error_msg()
                )
            );
        }

        return $result;
    }

    public function process(): EmailMessageInterface
    {
        $headers = $this->processHeaders();
        $body = $this->processBody();
        $attachments = $this->processAttachments();

        $subject = $this->processSubject($headers);
        $recipients = $this->processRecipients($headers);
        $senders = $this->processSenders($headers);

        return new EmailMessage(
            $headers,
            $senders,
            $recipients,
            $body,
            $subject,
            $attachments
        );
    }

    private function processBody(): string
    {
        if (!isset($this->raw_decoded_message['body'])) {
            throw new InvalidArgumentException('Email must have content');
        }

        $body = [];
        foreach ($this->raw_decoded_message['body'] as $body_part) {
            $body[$body_part['content_type']] = $body_part['content'];
        }

        return $body['text/html'] ?? $body['text/plain'] ?? '';
    }

    private function processHeaders(): array
    {
        if (!isset($this->raw_decoded_message['header'])) {
            throw new InvalidArgumentException('Email must have headers');
        }

        $headers = $this->raw_decoded_message['header'];

        if (isset($this->raw_decoded_message['header']['header'])) {
            foreach ($this->raw_decoded_message['header']['header'] as $header_name => $value) {
                if (isset($headers[$header_name])) {
                    continue; // don't override headers which are already set
                }

                if (is_array($value) && count($value) === 1) {
                    $headers[$header_name] = $value[0];

                    continue;
                }

                $headers[$header_name] = $value;
            }
        }

        return $headers;
    }

    private function processSubject(array $headers = []): string
    {
        return $headers['subject'] ?? '';
    }

    private function processSenders(array $headers = []): array
    {
        if (!isset($headers['from'])) {
            throw new InvalidArgumentException('Email must have sender');
        }

        return [$headers['from']];
    }

    private function processRecipients(array $headers = []): array
    {
        $recipients = [];

        foreach ($headers as $header_name => $value) {
            if (in_array($header_name, self::RECIPIENT_HEADERS, true)) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $recipients[] = $item;
                    }
                } else {
                    $recipients[] = $value;
                }
            }
        }

        return array_unique($recipients);
    }

    private function processAttachments(): array
    {
        $raw_attachments = $this->raw_decoded_message['attachments'] ?? [];

        $attachments = [];

        foreach ($raw_attachments as $raw_attachment) {
            $attachment = [
                'base64' => $raw_attachment['base64'],
                'filename' => $raw_attachment['filename'],
                'mime_type' => $raw_attachment['mime_type'],
                'size' => $raw_attachment['size'],
                'md5_hash' => $raw_attachment['md5_hash'],
                'content_disposition' => $raw_attachment['content-disposition'] ?? 'attachment',
            ];

            if (!empty($raw_attachment['content-id'])) {
                $attachment['attachment_id'] = trim($raw_attachment['content-id'], '<>');
            } elseif (!empty($raw_attachment['x-attachment-id'])) {
                $attachment['attachment_id'] = $raw_attachment['x-attachment-id'];
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
