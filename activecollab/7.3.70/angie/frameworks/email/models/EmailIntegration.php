<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Imap\TestConnection as TestImapConnection;
use ActiveCollab\ActiveCollabJobs\Jobs\Smtp\TestConnection as TestSmtpConncetion;

class EmailIntegration extends Integration
{
    const JOBS_QUEUE_CHANNEL = 'mail';

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Email';
    }

    public function getShortName(): string
    {
        return 'email';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Use SMTP and IMAP servers to send email notification and receive replies');
    }

    /**
     * Return true if this integration is available for on-demand packages.
     *
     * @return bool
     */
    public function isAvailableForOnDemand()
    {
        return false; // Use pre-configured mailing and don't let settings be changed via API
    }

    /**
     * Test SMTP connection.
     *
     * @param  string    $host
     * @param  int       $port
     * @param  string    $security
     * @param  string    $username
     * @param  string    $password
     * @param  bool|true $execute_now
     * @return mixed
     */
    public function testSmtpConnection($host, $port, $security, $username, $password, $execute_now = true)
    {
        $job = new TestSmtpConncetion(
            [
                'instance_id' => AngieApplication::getAccountId(),
                'host' => $host,
                'port' => $port,
                'security' => $security,
                'username' => $username,
                'password' => $password,
            ]
        );

        if ($execute_now) {
            return AngieApplication::jobs()->execute($job);
        } else {
            // Used just for job creation testing. Connection testing in the background is kind of pointless
            return AngieApplication::jobs()->dispatch($job);
        }
    }

    /**
     * Test IMAP connection.
     *
     * @param  string    $host
     * @param  int       $port
     * @param  string    $security
     * @param  string    $username
     * @param  string    $password
     * @param  string    $mailbox
     * @param  bool|true $execute_now
     * @return mixed
     */
    public function testImapConnection($host, $port, $security, $username, $password, $mailbox, $execute_now = true)
    {
        $job = new TestImapConnection(
            [
                'instance_id' => AngieApplication::getAccountId(),
                'host' => $host,
                'port' => $port,
                'security' => $security,
                'username' => $username,
                'password' => $password,
                'mailbox' => $mailbox,
            ]
        );

        if ($execute_now) {
            return AngieApplication::jobs()->execute($job);
        } else {
            // Used just for job creation testing. Connection testing in the background is kind of pointless
            return AngieApplication::jobs()->dispatch($job);
        }
    }

    // ---------------------------------------------------
    //  Settings
    // ---------------------------------------------------

    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->getAdditionalProperty('sender_name', AngieApplication::getName());
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setSenderName($value)
    {
        return $this->setAdditionalProperty('sender_name', trim($value));
    }

    /**
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->getAdditionalProperty('sender_email');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setSenderEmail($value)
    {
        return $this->setAdditionalProperty('sender_email', trim($value));
    }

    /**
     * @return string
     */
    public function getSmtpHost()
    {
        return $this->getAdditionalProperty('smtp_host');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setSmtpHost($value)
    {
        return $this->setAdditionalProperty('smtp_host', trim($value));
    }

    /**
     * @return int
     */
    public function getSmtpPort()
    {
        return $this->getAdditionalProperty('smtp_port', 587);
    }

    /**
     * @param  int $value
     * @return int
     */
    public function setSmtpPort($value)
    {
        return $this->setAdditionalProperty('smtp_port', (int) $value);
    }

    /**
     * @return string
     */
    public function getSmtpSecurity()
    {
        return $this->getAdditionalProperty('smtp_security', 'auto');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setSmtpSecurity($value)
    {
        return $this->setAdditionalProperty('smtp_security', trim($value));
    }

    /**
     * @return string
     */
    public function getSmtpUsername()
    {
        return $this->getAdditionalProperty('smtp_username');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setSmtpUsername($value)
    {
        return $this->setAdditionalProperty('smtp_username', trim($value));
    }

    /**
     * @return string
     */
    public function getSmtpPassword()
    {
        return $this->getAdditionalProperty('smtp_password');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setSmtpPassword($value)
    {
        return $this->setAdditionalProperty('smtp_password', trim($value));
    }

    /**
     * @return bool
     */
    public function getSmtpVerifyCertificate()
    {
        return (bool) $this->getAdditionalProperty('smtp_verify_certificate', true);
    }

    /**
     * @param  bool $value
     * @return bool
     */
    public function setSmtpVerifyCertificate($value)
    {
        return $this->setAdditionalProperty('smtp_verify_certificate', (bool) $value);
    }

    /**
     * @return string
     */
    public function getImapHost()
    {
        return $this->getAdditionalProperty('imap_host');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setImapHost($value)
    {
        return $this->setAdditionalProperty('imap_host', trim($value));
    }

    /**
     * @return int
     */
    public function getImapPort()
    {
        return $this->getAdditionalProperty('imap_port', 143);
    }

    /**
     * @param  int $value
     * @return int
     */
    public function setImapPort($value)
    {
        return $this->setAdditionalProperty('imap_port', (int) $value);
    }

    /**
     * @return string
     */
    public function getImapSecurity()
    {
        return $this->getAdditionalProperty('imap_security', 'auto');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setImapSecurity($value)
    {
        return $this->setAdditionalProperty('imap_security', trim($value));
    }

    /**
     * @return string
     */
    public function getImapUsername()
    {
        return $this->getAdditionalProperty('imap_username');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setImapUsername($value)
    {
        return $this->setAdditionalProperty('imap_username', trim($value));
    }

    /**
     * @return string
     */
    public function getImapPassword()
    {
        return $this->getAdditionalProperty('imap_password');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setImapPassword($value)
    {
        return $this->setAdditionalProperty('imap_password', trim($value));
    }

    /**
     * @return string
     */
    public function getImapMailbox()
    {
        return $this->getAdditionalProperty('imap_mailbox', 'INBOX');
    }

    /**
     * @param  string $value
     * @return string
     */
    public function setImapMailbox($value)
    {
        return $this->setAdditionalProperty('imap_mailbox', trim($value));
    }

    /**
     * @return bool
     */
    public function getImapVerifyCertificate()
    {
        return (bool) $this->getAdditionalProperty('imap_verify_certificate', true);
    }

    /**
     * @param  bool $value
     * @return bool
     */
    public function setImapVerifyCertificate($value)
    {
        return $this->setAdditionalProperty('imap_verify_certificate', (bool) $value);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'sender_name' => $this->getSenderName(),
            'sender_email' => $this->getSenderEmail(),
            'smtp_host' => $this->getSmtpHost(),
            'smtp_port' => $this->getSmtpPort(),
            'smtp_security' => $this->getSmtpSecurity(),
            'smtp_username' => $this->getSmtpUsername(),
            'smtp_password' => $this->getSmtpPassword(),
            'smtp_verify_certificate' => $this->getSmtpVerifyCertificate(),
            'imap_host' => $this->getImapHost(),
            'imap_port' => $this->getImapPort(),
            'imap_security' => $this->getImapSecurity(),
            'imap_username' => $this->getImapUsername(),
            'imap_password' => $this->getImapPassword(),
            'imap_mailbox' => $this->getImapMailbox(),
            'imap_verify_certificate' => $this->getImapVerifyCertificate(),
        ]);
    }

    /**
     * Returns true if cron jobs are configured properly and are being fired.
     *
     * @return bool
     */
    public function isOk(array & $error_messages = null)
    {
        if (!$this->getSenderEmail()) {
            if ($error_messages === null) {
                return false;
            } else {
                $error_messages[] = lang('Notification email is not set');
            }
        }

        if (!$this->getSmtpHost()) {
            if ($error_messages === null) {
                return false;
            } else {
                $error_messages[] = lang('SMTP connection parameters are not configured');
            }
        }

        if (!$this->getImapHost()) {
            if ($error_messages === null) {
                return false;
            } else {
                $error_messages[] = lang('IMAP parameters are not configured');
            }
        }

        return empty($error_messages);
    }
}
