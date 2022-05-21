<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Notifications\Channel;

use Angie\Inflector;
use ConfigOptions;
use InvalidParamError;
use IUser;
use Notification;
use NotImplementedError;
use User;

abstract class NotificationChannel
{
    private ?string $short_name = null;

    public function getShortName(): string
    {
        if ($this->short_name === null) {
            $class_name = get_class($this);

            $this->short_name = Inflector::underscore(substr($class_name, 0, strlen($class_name) - 19));
        }

        return $this->short_name;
    }

    /**
     * Return verbose name of the channel.
     *
     * @return string
     */
    abstract public function getVerboseName();

    // ---------------------------------------------------
    //  Enable / Disable / Settings
    // ---------------------------------------------------

    /**
     * Returns true if this channel is enabled by default.
     *
     * @return bool
     */
    public function isEnabledByDefault()
    {
        return $this->canOverrideDefaultStatus() ? ConfigOptions::getValue($this->getShortName() . '_notifications_enabled') : true;
    }

    /**
     * Set enabled by default.
     *
     * @param bool $value
     */
    public function setEnabledByDefault($value)
    {
        if ($this->canOverrideDefaultStatus()) {
            ConfigOptions::setValue($this->getShortName() . '_notifications_enabled', (bool) $value);
        } else {
            throw new NotImplementedError(__METHOD__);
        }
    }

    /**
     * Returns true if this channel is enabled for this user.
     *
     * @return bool
     */
    public function isEnabledFor(User $user)
    {
        if ($this->canOverrideDefaultStatus()) {
            return ConfigOptions::getValueFor($this->getShortName() . '_notifications_enabled', $user);
        } else {
            return $this->isEnabledByDefault();
        }
    }

    /**
     * Set enabled for given user.
     *
     * @param bool|null $value
     */
    public function setEnabledFor(User $user, $value)
    {
        if ($value === true || $value === false) {
            ConfigOptions::setValueFor($this->getShortName() . '_notifications_enabled', $user, $value);
        } elseif ($value === null) {
            ConfigOptions::removeValuesFor($user, $this->getShortName() . '_notifications_enabled');
        } else {
            throw new InvalidParamError('value', $value, '$value can be BOOL value or NULL');
        }
    }

    /**
     * Returns true if $user can override default enable / disable status.
     */
    public function canOverrideDefaultStatus(): bool
    {
        return (bool) ConfigOptions::exists($this->getShortName() . '_notifications_enabled');
    }

    // ---------------------------------------------------
    //  Open / Close
    // ---------------------------------------------------

    /**
     * Open channel for sending.
     */
    public function open()
    {
    }

    /**
     * Close channel after notifications have been sent.
     *
     * @param bool $sending_interupted
     */
    public function close($sending_interupted = false)
    {
    }

    abstract public function send(
        Notification &$notification,
        IUser $recipient,
        bool $skip_sending_queue = false
    );
}
