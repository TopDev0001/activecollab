<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package angie.frameworks.email
 * @subpackage models
 */
final class IncomingMailMessage
{
    /**
     * @var string
     */
    private $sender;

    /**
     * @var array
     */
    private $recipients;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $attachments = [];

    /**
     * @var array
     */
    private $references;

    private $mailer;

    /**
     * @param string $sender
     * @param array  $recipients
     * @param string $subject
     * @param string $body
     * @param array  $references
     * @param array  $attachments
     * @param string $mailer
     */
    public function __construct($sender, $recipients, $subject, $body, $references, $attachments, $mailer)
    {
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
        $this->references = $references;
        $this->mailer = $mailer;
    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return array
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @return array
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @return array
     */
    public function getTrimmedReferences()
    {
        $result = [];

        foreach ($this->references as $reference_to_search) {
            $result[] = rtrim(ltrim($reference_to_search, '<'), '>');
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getMailer()
    {
        return $this->mailer;
    }
}
