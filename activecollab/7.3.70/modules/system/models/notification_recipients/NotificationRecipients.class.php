<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\NotificationRecipientEvents\NotificationRecipientDeletedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\NotificationRecipientEvents\NotificationRecipientUpdatedEvent;

/**
 * NotificationRecipients class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class NotificationRecipients extends BaseNotificationRecipients
{
    public static function updateReadStatus(int $recipient_id, array $notification_ids = [], bool $markAsRead = true): void
    {
        $new_read_status = $markAsRead ? DateTimeValue::now() : null;

        if (count($notification_ids) > 0) {
            foreach ($notification_ids as $notification_id) {
                $conditions = [
                    'recipient_id' => $recipient_id,
                    'notification_id' => $notification_id,
                ];

                if ($markAsRead) {
                    $conditions['read_on'] = null;
                }

                $notification_recipient = NotificationRecipients::findOneBy($conditions);
                if ($notification_recipient && $notification_recipient instanceof NotificationRecipient) {
                    $notification_recipient->setReadOn($new_read_status);
                    $notification_recipient->save();
                    DataObjectPool::announce(new NotificationRecipientUpdatedEvent($notification_recipient));
                }
            }
        } else {
            $query_for_mark_as_read = 'SELECT * FROM notification_recipients WHERE recipient_id = ? AND read_on IS NULL';
            $query_for_mark_as_unread = 'SELECT * FROM notification_recipients WHERE recipient_id = ? AND read_on IS NOT NULL';
            $result = NotificationRecipients::findBySQL($markAsRead ? $query_for_mark_as_read : $query_for_mark_as_unread, $recipient_id);
            if ($result && $result->count() > 0) {
                /** @var NotificationRecipient $notification_recipient */
                foreach ($result as $notification_recipient) {
                    $notification_recipient->setReadOn($new_read_status);
                    $notification_recipient->save();
                    DataObjectPool::announce(new NotificationRecipientUpdatedEvent($notification_recipient));
                }
            }
        }
    }

    public static function deleteBy(array $notification_ids = [], array $recipient_ids = [], array $notification_recipients_ids = []): void
    {
        if (count($notification_ids) > 0 || count($recipient_ids) > 0 || count($notification_recipients_ids) > 0) {
            $conditions = [];

            if (count($notification_ids) > 0) {
                $conditions['notification_id'] = $notification_ids;
            }

            if (count($recipient_ids) > 0) {
                $conditions['recipient_id'] = $recipient_ids;
            }

            if (count($notification_recipients_ids) > 0) {
                $conditions['id'] = $notification_recipients_ids;
            }

            $result = NotificationRecipients::findBy($conditions);

            if ($result) {
                /** @var NotificationRecipient $notification_recipient */
                foreach ($result->toArray() as $notification_recipient) {
                    DataObjectPool::announce(new NotificationRecipientDeletedEvent($notification_recipient));
                    $notification_recipient->delete();
                }
            }
        }
    }
}
