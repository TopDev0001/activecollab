<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * NotificationRecipient class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
final class NotificationRecipient extends BaseNotificationRecipient
{
    public function canUserSeeThis(User $user)
    {
        return $user->getId() === $this->getRecipientId();
    }

    public function whoCanSeeThis()
    {
        return [$this->getRecipientId()];
    }

    public function isReadByRecipient(): bool
    {
        return $this->getReadOn() instanceof DateTimeValue;
    }

    public function jsonSerialize(): array
    {
        $notification_id = $this->getNotificationId();

        $result = parent::jsonSerialize();
        $result['notification_id'] = $notification_id;
        $result['read_on'] = $this->getReadOn();
        $result['notification_parent_type'] = '';
        $result['notification_parent_id'] = 0;

        ['parent_type' => $notification_parent_type, 'parent_id' => $notification_parent_id] = DB::executeFirstRow('SELECT parent_type, parent_id FROM notifications WHERE id = ?', $notification_id);

        if ($notification_parent_type) {
            $result['notification_parent_type'] = $notification_parent_type;
        }

        if ($notification_parent_id) {
            $result['notification_parent_id'] = $notification_parent_id;
        }

        return $result;
    }
}
