<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class CustomReminder extends Reminder
{
    private ?array $subscriber_ids = null;

    /**
     * Override default set attribute method.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        if ($attribute == 'subscribers' && $this->isValidSubscribersValue($value)) {
            $this->subscriber_ids = $value;
        }

        parent::setAttribute($attribute, $value);
    }

    private function isValidSubscribersValue($value): bool
    {
        return $value === null || is_array($value);
    }

    /**
     * Save record to the database.
     */
    public function save()
    {
        parent::save();

        if (!is_array($this->subscriber_ids) || !in_array($this->getCreatedBy()->getId(), $this->subscriber_ids)) {
            $this->unsubscribe($this->getCreatedBy());
        }
    }

    /**
     * Send custom reminder notification.
     */
    public function send()
    {
        if ($subscribers = $this->getSubscribers()) {
            $parent = $this->getParent();

            if ($parent instanceof ApplicationObject && $parent->isAccessible()) {
                /** @var CustomReminderNotification $notification */
                $notification = AngieApplication::notifications()->notifyAbout(RemindersFramework::INJECT_INTO . '/custom_reminder', $parent);
                $notification
                    ->setReminder($this)
                    ->sendToUsers($subscribers, true);
            }
        }
    }

    public function canView(User $user): bool
    {
        $parent = $this->getParent();

        if (empty($parent)) {
            return false;
        }

        return $parent->canView($user);
    }
}
