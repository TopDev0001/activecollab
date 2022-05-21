<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Smtp;

use ActiveCollab\ActiveCollabJobs\Utils\JobDataResolver\JobDataResolver;
use ActiveCollab\ActiveCollabJobs\Utils\MailRouter\MailRouterInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class SendMessage extends Job
{
    public function __construct(array $data = null)
    {
        $data['use_native_mailer'] = !empty($data['use_native_mailer']);
        $data['require_smtp_connection_data'] = !empty($data['require_smtp_connection_data']);

        if (!$data['use_native_mailer'] && $data['require_smtp_connection_data']) {
            foreach (['smtp_host', 'smtp_port', 'smtp_security'] as $required_argument) {
                if (empty($data[$required_argument])) {
                    throw new InvalidArgumentException("'$required_argument' property is required");
                }
            }
        }

        if (empty($data['smtp_username'])) {
            $data['smtp_username'] = '';
        }

        if (empty($data['smtp_password'])) {
            $data['smtp_password'] = '';
        }

        if (empty($data['instance_id_in_reply_to'])) {
            $data['instance_id_in_reply_to'] = false;
        }

        if (empty($data['route'])) {
            $data['route'] = null;
        }

        if (empty($data['from']) || !is_array($data['from']) || empty($data['from']['email'])) {
            throw new InvalidArgumentException("'from' property is required");
        }

        if (empty($data['to']) || !is_array($data['to']) || empty($data['to']['email'])) {
            throw new InvalidArgumentException("'to' property is required");
        }

        foreach (['subject', 'body', 'service_address'] as $required_argument) {
            if (empty($data[$required_argument])) {
                throw new InvalidArgumentException("'$required_argument' property is required");
            }
        }

        if (empty($data['route_reply_to'])) {
            $data['route_reply_to'] = false;
        }

        if (empty($data['message_id'])) {
            $data['message_id'] = '';
        }

        if (empty($data['entity_ref_id'])) {
            $data['entity_ref_id'] = '';
        }

        if (empty($data['attachments'])) {
            $data['attachments'] = [];
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $mailer = $this->getContainer()
            ->get(MailRouterInterface::class)
                ->createFromJobData(new JobDataResolver($this->getData()));

        $instance_id = (int) $this->getData('instance_id');

        $from = $this->getData('from');
        $recipient = $this->getData('to');

        $mailer->CharSet = 'utf-8';
        $mailer->Encoding = '8bit';

        $mailer->From = $from['email'];
        $mailer->FromName = $from['name'];

        $mailer->Sender = $from['email']; // Force -f mail() function param

        $mailer->addAddress($recipient['email'], $recipient['name']);

        // ---------------------------------------------------
        //  Configure email replies
        // ---------------------------------------------------

        $message_parent_type = null;
        $message_parent_id = null;

        if (is_array($this->getData('route_reply_to'))) {
            [
                $message_parent_type,
                $message_parent_id,
            ] = $this->getData('route_reply_to');
        }

        $route_reply_to_address = $this->getReplyToAddress();

        if ($route_reply_to_address) {
            $mailer->addReplyTo($route_reply_to_address);
        }

        $mailer->addCustomHeader('Return-Path', $this->getData('service_address'));

        if (!empty($this->getData('message_id'))) {
            $mailer->MessageID = $this->getData('message_id');
        }

        // ---------------------------------------------------
        //  Subject and body
        // ---------------------------------------------------

        $subject = $this->getData('subject');
        $body = $this->getData('body');

        $mailer->addCustomHeader('Auto-Submitted', 'auto-generated');
        $mailer->addCustomHeader('Precedence', 'bulk');

        if ($entity_ref_id = $this->getData()['entity_ref_id']) {
            $mailer->addCustomHeader('X-Entity-Ref-ID', $entity_ref_id);
        }

        $mailer->isHTML(true);

        $mailer->Subject = $subject;
        $mailer->Body = $body;

        if ($attachments = $this->getData()['attachments']) {
            foreach ($attachments as $attachment) {
                $mailer->addAttachment($attachment['path'], (empty($attachment['name']) ? '' : $attachment['name']));
            }
        }

        // ---------------------------------------------------
        //  Send
        // ---------------------------------------------------

        try {
            $mailer->send();

            $this->logMessageSent(
                $instance_id,
                (string) $message_parent_type,
                (int) $message_parent_id,
                $from['name'] ? trim($from['name'] . ' <' . $from['email'] . '>') : $from['email'],
                $recipient['name'] ? trim($recipient['name'] . ' <' . $recipient['email'] . '>') : $recipient['email'],
                $subject,
                trim(trim($mailer->getLastMessageID(), '<'), '>')
            );
        } catch (Exception $e) {
            throw new RuntimeException($mailer->ErrorInfo);
        } finally {
            $mailer->smtpClose();
        }
    }

    public function getReplyToAddress(): ?string
    {
        $route_reply_to = $this->getData('route_reply_to');

        if (!is_string($route_reply_to) && !is_array($route_reply_to)) {
            return null;
        }

        if (is_string($route_reply_to)) {
            return $route_reply_to;
        }

        $service_address = $this->getData('service_address');
        $instance_id = $this->getData('instance_id');

        if (!$this->getData('instance_id_in_reply_to')) {
            return $service_address;
        }

        $service_address_bits = explode('@', $service_address);

        return sprintf(
            '%s-%d@%s',
            array_shift($service_address_bits),
            $instance_id,
            implode('', $service_address_bits),
        );
    }

    private function logMessageSent(
        int $instance_id,
        string $parent_type,
        int $parent_id,
        string $sender,
        string $recipient,
        string $subject,
        string $message_id
    ): void {
        if (empty($parent_type)) {
            $parent_type = '';
        }

        if ($parent_id < 1) {
            $parent_id = 0;
        }

        $this->connection->execute(
            'INSERT INTO email_log (instance_id, parent_type, parent_id, sender, recipient, subject, message_id, sent_on) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            $instance_id,
            $parent_type,
            $parent_id,
            mb_substr($sender, 0, 191),
            mb_substr($recipient, 0, 191),
            mb_substr($subject, 0, 191),
            mb_substr($message_id, 0, 191)
        );
    }
}
