<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class Notifications extends BaseNotifications
{
    const READ_CACHE_KEY = 'notifications_read';

    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'notifications_for_recipient') || str_starts_with($collection_name, 'unread_notifications_for_recipient')) {
            return self::prepareNotificationsForRecipientCollection($collection_name, $user);
        }

        throw new InvalidParamError('collection_name', $collection_name);
    }

    /**
     * @param  string                      $collection_name
     * @param  User|null                   $user
     * @return UserNotificationsCollection
     */
    private static function prepareNotificationsForRecipientCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        $recipient = DataObjectPool::get(User::class, array_pop($bits));

        if ($recipient instanceof User && $user->isActive()) {
            $collection = parent::prepareCollection($collection_name, $user);

            $collection->setOrderBy('created_on DESC, id DESC');
            $collection->setJoinTable('notification_recipients', 'notification_id');

            if (str_starts_with($collection_name, 'notifications_for_recipient')) {
                $collection->setConditions('notification_recipients.recipient_id = ?', $recipient->getId());
            } else {
                $collection->setConditions('notification_recipients.recipient_id = ? AND notification_recipients.read_on IS NULL', $recipient->getId());
            }

            return $collection;
        }

        throw new ImpossibleCollectionError('Recipient not found or found, but not active');
    }

    // ---------------------------------------------------
    //  Read/Unread
    // ---------------------------------------------------

    /**
     * Returns true if $user has read context in which notification was published.
     *
     * @param  Notification|int $notification
     * @param  bool             $use_cache
     * @param  bool             $rebuild_stale_cache
     * @return bool
     */
    public static function isRead($notification, User $user, $use_cache = true, $rebuild_stale_cache = true)
    {
        if ($user instanceof User) {
            return self::isReadTimestampSet($notification, $user, $use_cache, $rebuild_stale_cache);
        }

        throw new InvalidInstanceError('user', $user, 'User');
    }

    /**
     * Mark a single notification as read.
     */
    public static function markRead(Notification $notification, User $user)
    {
        if (!self::isRead($notification, $user, false, false)) {
            NotificationRecipients::updateReadStatus($user->getId(), [$notification->getId()]);

            AngieApplication::cache()->removeByObject($notification);

            // Update read cache only if cache value exists (if not, system will rebuild it the first time it is needed)
            $cached_value = AngieApplication::cache()->getByObject($user, self::READ_CACHE_KEY);

            if (is_array($cached_value)) {
                $cached_value[$notification->getId()] = true;

                AngieApplication::cache()->setByObject($user, self::READ_CACHE_KEY, $cached_value);
            }
        }
    }

    /**
     * Mark all unread notifications for a given object as read.
     *
     * @param ApplicationObject|array $parent
     */
    public static function markReadByParent($parent, User $user)
    {
        if (is_array($parent) && isset($object[0]) && $object[1]) {
            [$parent_type, $parent_id] = $parent;
        } elseif ($parent instanceof ApplicationObject) {
            $parent_type = get_class($parent);
            $parent_id = $parent->getId();
        } else {
            throw new InvalidParamError(
                'parent',
                $parent,
                '$parent is expected to be an instance of ApplicationObject class of Class-ID pair'
            );
        }

        $user_id = $user->getId();

        if ($notification_ids = self::getNotificationIdsByParentAndUser($parent_type, $parent_id, $user_id)) {
            try {
                $cached_read_values = self::getReadCache($user, self::READ_CACHE_KEY);

                DB::beginWork('Marking parent notification as read @ ' . __CLASS__);

                foreach ($notification_ids as $notification_id) {
                    NotificationRecipients::updateReadStatus($user->getId(), [$notification_id]);

                    if ($cached_read_values && is_array($cached_read_values)) {
                        $cached_read_values[$notification_id] = true;
                    }
                }

                DB::commit('Parent notification has been marked as read @ ' . __CLASS__);

                if (is_array($cached_read_values)) {
                    AngieApplication::cache()->setByObject($user, self::READ_CACHE_KEY, $cached_read_values);
                }
            } catch (Exception $e) {
                DB::rollback('Failed to mark parent notification as read @ ' . __CLASS__);
                throw $e;
            }
        }

        AngieApplication::cache()->removeByModel('notifications');
    }

    /**
     * Mark a single notification as unread.
     */
    public static function markUnread(Notification $notification, User $user)
    {
        if (self::isRead($notification, $user, false, false)) {
            NotificationRecipients::updateReadStatus($user->getId(), [$notification->getId()], false);
            AngieApplication::cache()->removeByObject($notification);

            // Update read cache only if cache value exists (if not, system will rebuild it the first time it is needed)
            $cached_value = AngieApplication::cache()->getByObject($user, self::READ_CACHE_KEY);

            if (is_array($cached_value)) {
                $cached_value[$notification->getId()] = false;

                AngieApplication::cache()->setByObject($user, self::READ_CACHE_KEY, $cached_value);
            }
        }
    }

    /**
     * Mass-change read status for given user.
     *
     * @param        $new_read_status
     * @param  bool  $all_notifications
     * @param  null  $notification_ids
     * @return array
     */
    public static function updateReadStatusForRecipient(User $user, $new_read_status, $all_notifications = true, $notification_ids = null)
    {
        if ($all_notifications) {
            NotificationRecipients::updateReadStatus($user->getId(), [], $new_read_status);
        } else {
            if ($notification_ids) {
                NotificationRecipients::updateReadStatus($user->getId(), $notification_ids, $new_read_status);
            } else {
                throw new InvalidParamError('notification_ids', $notification_ids, 'Missing notification ID-s');
            }
        }

        AngieApplication::cache()->removeByObject($user, self::READ_CACHE_KEY);

        return [];
    }

    /**
     * Returns true if $field_name is set to a non-null value for a given recipient and a given notification.
     *
     * This method is cache aware and it will maintain or rebuild cache if needed, based on provided parameters
     *
     * @param  Notification|int $notification
     * @param  bool             $use_cache
     * @param  bool             $rebuild_stale_cache
     * @return bool
     */
    private static function isReadTimestampSet($notification, User $user, $use_cache = true, $rebuild_stale_cache = true)
    {
        $notification_id = $notification instanceof Notification ? $notification->getId() : $notification;

        if (empty($use_cache) && empty($rebuild_stale_cache)) {
            return (bool) DB::executeFirstCell('SELECT COUNT(*) FROM notification_recipients WHERE notification_id = ? AND recipient_id = ? AND read_on IS NOT NULL', $notification_id, $user->getId());
        }

        $cached_values = self::getReadCache($user, self::READ_CACHE_KEY);

        return isset($cached_values[$notification_id]) && $cached_values[$notification_id];
    }

    /**
     * Get read cache.
     *
     * @param  User  $user
     * @return array
     */
    private static function getReadCache($user)
    {
        return AngieApplication::cache()->getByObject($user, self::READ_CACHE_KEY, function () use ($user) {
            $result = [];

            if ($rows = DB::execute('SELECT notification_id, read_on FROM notification_recipients WHERE recipient_id = ?', $user->getId())) {
                foreach ($rows as $row) {
                    $result[$row['notification_id']] = (bool) $row['read_on'];
                }
            }

            return $result;
        });
    }

    /**
     * Clear all notifications for a given recipient.
     *
     * @param bool      $all_notifications
     * @param array|int $notification_ids
     */
    public static function clearForRecipient(User $user, $all_notifications = true, $notification_ids = null)
    {
        if ($all_notifications) {
            NotificationRecipients::deleteBy([], [$user->getId()]);
        } else {
            if ($notification_ids) {
                NotificationRecipients::deleteBy($notification_ids, [$user->getId()]);
            } else {
                throw new InvalidParamError('notification_ids', $notification_ids, 'Missing notification ID-s');
            }
        }

        AngieApplication::cache()->removeByObject($user, self::READ_CACHE_KEY);
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Delete notifications by parent object.
     *
     * @param ApplicationObject $parent
     */
    public static function deleteByParent($parent)
    {
        if ($notification_ids = DB::executeFirstColumn('SELECT id FROM notifications WHERE ' . self::parentToCondition($parent))) {
            NotificationRecipients::deleteBy($notification_ids);
            DB::execute('DELETE FROM notifications WHERE id IN (?)', $notification_ids);
        }
    }

    /**
     * Delete notifications by parent object and notification type.
     *
     * @param  ApplicationObject $parent
     * @param  string            $type
     * @throws DBQueryError
     * @throws InvalidParamError
     */
    public static function deleteByParentAndType($parent, $type)
    {
        if ($notification_ids = DB::executeFirstColumn('SELECT id FROM notifications WHERE parent_id = ? AND parent_type = ? AND type = ?', $parent->getId(), $parent->getType(), $type)) {
            NotificationRecipients::deleteBy($notification_ids);
            DB::execute('DELETE FROM notifications WHERE id IN (?)', $notification_ids);
        }
    }

    /**
     * Delete logged activitys by parent and additional property.
     *
     * @param ApplicationObject $parent
     * @param string            $property_name
     * @param mixed             $property_value
     */
    public static function deleteByParentAndAdditionalProperty($parent, $property_name, $property_value)
    {
        if ($rows = DB::execute('SELECT id, raw_additional_properties FROM notifications WHERE parent_type = ? AND parent_id = ?', get_class($parent), $parent->getId())) {
            $to_delete = [];

            foreach ($rows as $row) {
                if ($row['raw_additional_properties']) {
                    $properties = unserialize($row['raw_additional_properties']);

                    if (empty($properties[$property_name])) {
                        continue;
                    }

                    if (($property_value instanceof Closure && $property_value($properties[$property_name])) || $properties[$property_name] == $property_value) {
                        $to_delete[] = $row['id'];
                    }
                }
            }

            if (count($to_delete)) {
                NotificationRecipients::deleteBy($to_delete);
                DB::execute('DELETE FROM notifications WHERE id IN (?)', $to_delete);
            }
        }
    }

    /**
     * Clean up old notifications.
     */
    public static function cleanUp()
    {
        if ($ids = DB::executeFirstColumn('SELECT id FROM notifications WHERE created_on < ?', DateValue::makeFromString('-30 days'))) {
            DB::transact(function () use ($ids) {
                DB::execute('DELETE FROM notification_recipients WHERE notification_id IN (?)', $ids);
                DB::execute('DELETE FROM notifications WHERE id IN (?)', $ids);
            }, 'Cleaning up old notifications');
        }
    }

    /**
     * Get new notification.
     * @param  int        $id
     * @return array
     * @throws \Exception
     */
    public static function getRecentUpdate(User $user, $id)
    {
        $hash_url = '';
        $updates = [];
        $reactions = [];
        $read_notification_ids = [];

        /** @var Notification $notification */
        $notification = Notifications::findById($id);

        if (!$notification) {
            AngieApplication::log()->info('Notification ' . $id . ' not found');

            return [
                'objects_and_updates' => [],
                'related' => [],
            ];
        }

        $object = DataObjectPool::get($notification->getParentType(), $notification->getParentId());

        $notification_ids = DB::executeFirstColumn(
            'SELECT n.id AS "id"
                FROM `notifications` n INNER JOIN `notification_recipients` nr ON n.`id` = nr.`notification_id`
                WHERE n.`parent_type` = ? AND n.`parent_id` = ? AND nr.`recipient_id` = ?',
            $object->getType(),
            $object->getId(),
            $user->getId()
        );

        if ($object->fieldExists('project_id')) {
            $parent = Projects::findById($object->getFieldValue('project_id'));
            $hash_url = $object->getGlobalReference();
        } elseif ($object->fieldExists('calendar_id')) {
            $parent = Calendars::findById($object->getFieldValue('calendar_id'));
        } else {
            $parent = $object;
        }

        $type_ids_map = [];

        if (!empty($notification_ids)) {
            /** @var Notification[] $notifications */
            $notifications = Notifications::findByIds($notification_ids, true);

            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    if (!$notification->isRead($user)) {
                        $notification->onObjectUpdateFlags($updates);
                        $notification->onObjectReactionFlags($reactions);

                        if ($notification->isUserMentioned($user)) {
                            $updates['mentions'][] = $notification->getId();
                        }
                    } else {
                        $read_notification_ids[] = $notification->getId();
                    }

                    $notification->onRelatedObjectsTypeIdsMap($type_ids_map);
                }
            }
        }

        $related = DataObjectPool::getByTypeIdsMap($type_ids_map);

        return [
            'objects_and_updates' => [
                'notification' => [
                    'id' => $object->getId(),
                    'type' => $object->getType(),
                    'name' => $object->getName(),
                    'last_update_on' => $notification->getCreatedOn()->getTimestamp(),
                    'reactions' => $reactions,
                    'updates' => $updates,
                    'view_url' => $object->getUrlPath(),
                    'hash_url' => $hash_url,
                    'parent_name' => $parent->getName(),
                    'read_notification_ids' => $read_notification_ids,
                ],
                'object' => $object,
            ],
            'related' => $related ? $related : [],
            'total_unread' => DB::executeFirstCell("SELECT COUNT(DISTINCT n.parent_type, n.parent_id) AS 'row_count' FROM notifications AS n LEFT JOIN notification_recipients AS nr ON n.id = nr.notification_id WHERE nr.recipient_id = ? AND nr.read_on IS NULL", $user->getId()),
        ];
    }

    /**
     * @throws InvalidParamError
     */
    private static function getNotificationIdsByParentAndUser($parent_type, $parent_id, $user_id): ?array
    {
        return DB::executeFirstColumn(
            "SELECT notifications.id AS 'id' 
             FROM notifications, notification_recipients 
             WHERE notifications.id = notification_recipients.notification_id 
             AND notifications.parent_type = ? 
             AND notifications.parent_id = ? 
             AND notification_recipients.recipient_id = ? 
             AND notification_recipients.read_on IS NULL",
            $parent_type,
            $parent_id,
            $user_id
        );
    }
}
