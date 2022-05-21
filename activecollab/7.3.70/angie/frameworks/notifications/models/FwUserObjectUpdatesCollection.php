<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class FwUserObjectUpdatesCollection extends CompositeCollection
{
    use IWhosAsking;

    public function getModelName(): string
    {
        return 'Users';
    }

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    /**
     * Run the query and return DB result.
     *
     * @return DbResult|DataObject[]
     */
    public function execute()
    {
        $preload_counts_for = [];

        /** @var Notification[] $notifications */
        if ($notifications = $this->getCurrentPageNotifications()) {
            $preloaded_notification_parents = $this->preloadNotificationParents($notifications);

            $objects = [];
            $updates = [];
            $reactions = [];
            $last_updates_on = [];
            $type_ids_map = [];
            $read_notification_ids = [];
            $senders = [];
            foreach ($notifications as $notification) {
                $notification_parent_type = $notification->getParentType();
                $notification_parent_id = $notification->getParentId();

                if ($notification_parent_type && $notification_parent_id) {
                    $parent_key = "{$notification_parent_type}-{$notification_parent_id}";
                    if (empty($objects[$parent_key]) && !empty($preloaded_notification_parents[$notification_parent_type][$notification_parent_id])) {
                        $objects[$parent_key] = $preloaded_notification_parents[$notification_parent_type][$notification_parent_id];
                    }

                    if (isset($objects[$parent_key])) {
                        if (empty($updates[$parent_key])) {
                            $updates[$parent_key] = [];
                        }
                        if (empty($reactions[$parent_key])) {
                            $reactions[$parent_key] = [];
                        }

                        if (empty($last_updates_on[$parent_key])) {
                            $last_updates_on[$parent_key] = $notification->getCreatedOn()->getTimestamp();
                        }

                        if (!$notification->isRead($this->recipient)) {
                            $senders[$parent_key][$notification->getId()] = $notification->getSenderId();
                            $notification->onObjectUpdateFlags($updates[$parent_key]);
                            $notification->onObjectReactionFlags($reactions[$parent_key]);

                            if ($this->recipient instanceof User && $notification->isUserMentioned($this->recipient)) {
                                $updates[$parent_key]['mentions'][] = $notification->getId();
                            }
                        } else {
                            $read_notification_ids[$parent_key][] = $notification->getId();
                        }
                    }
                }

                $notification->onRelatedObjectsTypeIdsMap($type_ids_map);
            }

            $preload_counts_for = $type_ids_map;

            $objects_and_updates = [];

            foreach ($objects as $key => $object) {
                if (isset($last_updates_on[$key])) {
                    $last_update_on = $last_updates_on[$key];
                } else {
                    if ($object instanceof IUpdatedOn) {
                        $last_update_on = $object->getUpdatedOn()->getTimestamp();
                    } else {
                        if ($object instanceof ICreatedOn) {
                            $last_update_on = $object->getCreatedOn()->getTimestamp();
                        } else {
                            $last_update_on = 0;
                        }
                    }
                }

                $object_class = get_class($object);

                if (empty($preload_counts_for[$object_class])) {
                    $preload_counts_for[$object_class] = [];
                }

                $preload_counts_for[$object_class][] = $object->getId();

                $objects_and_updates[] = [
                    'object' => $object,
                    'reactions' => isset($reactions[$key]) ? $reactions[$key] : [],
                    'updates' => isset($updates[$key]) ? $updates[$key] : [],
                    'last_update_on' => $last_update_on,
                    'is_subscribed' => $object instanceof ISubscriptions ? $object->isSubscribed($this->getWhosAsking()) : false,
                    'read_notification_ids' => array_key_exists($key, $read_notification_ids) ? $read_notification_ids[$key] : [],
                    'senders' => array_key_exists($key, $senders) ? $senders[$key] : null,
                ];
            }

            $related = DataObjectPool::getByTypeIdsMap($type_ids_map);
        } else {
            $objects_and_updates = [];
            $related = [];
        }

        $this->preloadCounts($preload_counts_for);

        return [
            'objects_and_updates' => $objects_and_updates,
            'related' => (empty($related) ? [] : $related),
            'total_unread' => $this->fetch_total_number_of_unread_objects ? $this->countUnread() : -1,
        ];
    }

    /**
     * Preload notifications.
     *
     * @param  Notification[] $notifications
     * @return array
     */
    private function preloadNotificationParents($notifications)
    {
        $type_ids_map = [];

        foreach ($notifications as $notification) {
            $parent_type = $notification->getParentType();

            if (empty($type_ids_map[$parent_type])) {
                $type_ids_map[$parent_type] = [];
            }

            $type_ids_map[$parent_type][] = $notification->getParentId();
        }

        $preloaded_objects = DataObjectPool::getByTypeIdsMap($type_ids_map);

        return $preloaded_objects ? $preloaded_objects : [];
    }

    /**
     * Preload counts for collection elements, and their related elements.
     */
    protected function preloadCounts(array $type_ids_map)
    {
        foreach ($type_ids_map as $preload_for_type => $preload_ids) {
            $reflection = new ReflectionClass($preload_for_type);

            if ($reflection->implementsInterface(IComments::class)) {
                Comments::preloadCountByParents($preload_for_type, $preload_ids);
            }

            if ($reflection->implementsInterface(IAttachments::class)) {
                Attachments::preloadDetailsByParents($preload_for_type, $preload_ids);
            }

            if ($reflection->implementsInterface(ILabels::class)) {
                Labels::preloadDetailsByParents($preload_for_type, $preload_ids);
            }
        }
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        return DB::executeFirstCell("SELECT COUNT(DISTINCT n.parent_type, n.parent_id) AS 'row_count' FROM notifications AS n LEFT JOIN notification_recipients AS nr ON n.id = nr.notification_id WHERE nr.recipient_id = ?", $this->recipient->getId());
    }

    /**
     * Return number of unread objects.
     *
     * @return int
     */
    public function countUnread()
    {
        return DB::executeFirstCell("SELECT COUNT(DISTINCT n.parent_type, n.parent_id) AS 'row_count' FROM notifications AS n LEFT JOIN notification_recipients AS nr ON n.id = nr.notification_id WHERE nr.recipient_id = ? AND nr.read_on IS NULL", $this->recipient->getId());
    }

    /**
     * @var User
     */
    private $recipient;

    /**
     * Set recipient.
     *
     * @return $this
     */
    public function &setRecipient(User $recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Should we fetch total number of unread objects flag.
     *
     * @var bool
     */
    private $fetch_total_number_of_unread_objects = false;

    /**
     * Should we fetch total number of unread objects.
     *
     * @param  bool  $yes_or_no
     * @return $this
     */
    public function &fetchTotalNumberOfUnreadObjects($yes_or_no)
    {
        $this->fetch_total_number_of_unread_objects = $yes_or_no;

        return $this;
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Return timestamp hash.
     *
     * @return string
     */
    public function getTimestampHash()
    {
        $notification_ids = $this->getCurrentPageNotificationIds();

        return sha1(
            sprintf(
                '%s,%s,%s',
                $this->recipient->getUpdatedOn()->toMySQL(),
                !empty($notification_ids)
                    ? DB::executeFirstCell(
                        "SELECT GROUP_CONCAT(n.created_on ORDER BY n.created_on DESC SEPARATOR ',') AS 'timestamp_hash' FROM notifications AS n LEFT JOIN notification_recipients AS nr ON n.id = nr.notification_id WHERE n.id IN (?) AND nr.recipient_id = ? ORDER BY n.created_on DESC, nr.id DESC",
                        $notification_ids,
                        $this->recipient->getId(),
                    )
                    : '',
                !empty($notification_ids)
                    ? DB::executeFirstCell(
                        "SELECT GROUP_CONCAT(read_on ORDER BY read_on DESC SEPARATOR ',') AS 'timestamp_hash' FROM notification_recipients WHERE recipient_id = ? AND read_on IS NOT NULL",
                        $this->recipient->getId(),
                    )
                    : '',
            ),
        );
    }

    /**
     * @var array
     */
    private $current_page_objects = false;

    /**
     * Return current page objects.
     *
     * @return array|bool
     */
    public function getCurrentPageObjects()
    {
        if ($this->current_page_objects === false) {
            $map = [];

            if ($rows = DB::execute('SELECT n.parent_type, n.parent_id FROM notifications AS n LEFT JOIN notification_recipients AS nr ON n.id = nr.notification_id WHERE nr.recipient_id = ? ORDER BY n.created_on DESC', $this->recipient->getId())) {
                foreach ($rows as $row) {
                    if (!in_array($row['parent_type'] . '-' . $row['parent_id'], $map)) {
                        $map[] = $row['parent_type'] . '-' . $row['parent_id'];
                    }
                }
            }

            $this->current_page_objects = [];

            foreach (array_slice($map, ($this->getCurrentPage() - 1) * $this->getItemsPerPage(), $this->getItemsPerPage()) as $parent) {
                [$parent_type, $parent_id] = explode('-', $parent);

                if (empty($this->current_page_objects[$parent_type])) {
                    $this->current_page_objects[$parent_type] = [
                        (int) $parent_id,
                    ];
                } else {
                    $this->current_page_objects[$parent_type][] = (int) $parent_id;
                }
            }
        }

        return $this->current_page_objects;
    }

    /**
     * @var array
     */
    private $current_page_notification_ids = false;

    /**
     * Return ID-s of the current page notifications.
     *
     * @return array
     */
    private function getCurrentPageNotificationIds()
    {
        if ($this->current_page_notification_ids === false) {
            $this->current_page_notification_ids = [];

            $conditions = [];

            if ($current_page_objects = $this->getCurrentPageObjects()) {
                foreach ($current_page_objects as $type => $ids) {
                    $conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $type, $ids);
                }
            }

            if (count($conditions)) {
                $this->current_page_notification_ids = DB::executeFirstColumn('SELECT id FROM notifications WHERE ' . implode(' OR ', $conditions));
            }
        }

        return $this->current_page_notification_ids;
    }

    /**
     * @return Notification[]|DBResult|null
     */
    private function getCurrentPageNotifications()
    {
        return !empty($this->getCurrentPageNotificationIds()) ?
            Notifications::findBySql('SELECT n.* FROM notifications AS n LEFT JOIN notification_recipients AS nr ON n.id = nr.notification_id WHERE n.id IN (?) AND nr.recipient_id = ? ORDER BY n.created_on DESC, n.id DESC', $this->getCurrentPageNotificationIds(), $this->recipient->getId())
            : null;
    }
}
