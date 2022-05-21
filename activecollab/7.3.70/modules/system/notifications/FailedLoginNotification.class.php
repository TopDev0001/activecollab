<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

/**
 * Application level failed login notification.
 *
 * @package ActiveCollab.modules.system
 * @subpackage notifications
 */
class FailedLoginNotification extends Notification
{
    /**
     * Return username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getAdditionalProperty('username');
    }

    /**
     * Set username.
     *
     * @param  string $value
     * @return $this
     */
    public function &setUsername($value)
    {
        $this->setAdditionalProperty('username', $value);

        return $this;
    }

    /**
     * Return max attempts.
     *
     * @return string
     */
    public function getMaxAttempts()
    {
        return $this->getAdditionalProperty('max_attempts');
    }

    /**
     * Set max attempts.
     *
     * @param  string $value
     * @return $this
     */
    public function &setMaxAttempts($value)
    {
        $this->setAdditionalProperty('max_attempts', $value);

        return $this;
    }

    /**
     * Return cooldown time in minutes.
     *
     * @return string
     */
    public function getCooldownInMinutes()
    {
        return $this->getAdditionalProperty('cooldown_in_minutes');
    }

    /**
     * Set cooldown time in minutes.
     *
     * @param  string $value
     * @return $this
     */
    public function &setCooldownInMinutes($value)
    {
        $this->setAdditionalProperty('cooldown_in_minutes', $value);

        return $this;
    }

    /**
     * Return from IP address.
     *
     * @return string
     */
    public function getFromIP()
    {
        return $this->getAdditionalProperty('from_ip');
    }

    /**
     * Set from IP address.
     *
     * @param  string $value
     * @return $this
     */
    public function &setFromIP($value)
    {
        $this->setAdditionalProperty('from_ip', $value);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'username' => $this->getUsername(),
            'max_attempts' => $this->getMaxAttempts(),
            'cooldown_in_minutes' => $this->getCooldownInMinutes(),
            'from_ip' => $this->getFromIP(),
        ];
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true; // Force email, regardless of settings
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
