<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * User notifications collection.
 *
 * @package angie.frameworks.notifications
 * @subpackage models
 */
class UserNotificationsCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var bool
     */
    private $unread_only = false;

    /**
     * Construct the collection.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        if (str_starts_with($name, 'unread')) {
            $this->unread_only = true;
        }
    }

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
        /** @var Notification[] $notifications */
        if ($notifications = $this->getNotificationsCollection()->execute()) {
            $type_ids_map = [];

            foreach ($notifications as $notification) {
                $parent_type = $notification->getParentType();

                if (empty($type_ids_map[$parent_type])) {
                    $type_ids_map[$parent_type] = [];
                }

                $type_ids_map[$parent_type][] = $notification->getParentId();

                $notification->onRelatedObjectsTypeIdsMap($type_ids_map);
            }

            $this->preloadCounts($type_ids_map);

            $related = DataObjectPool::getByTypeIdsMap($type_ids_map);
        } else {
            $notifications = [];
            $related = [];
        }

        return [
            'notifications' => $notifications,
            'related' => $related,
        ];
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

        if (!empty($type_ids_map[Project::class])) {
            Projects::preloadProjectElementCounts($type_ids_map[Project::class]);
        }
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        return $this->getNotificationsCollection()->count();
    }

    /**
     * @var User
     */
    private $recipient;

    /**
     * Set recipient.
     *
     * @return $this
     * @throws InvalidParamError
     */
    public function &setRecipient(User $recipient)
    {
        if ($recipient instanceof User) {
            $this->recipient = $recipient;
        } else {
            throw new InvalidParamError('recipient', $recipient, 'User');
        }

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
        return sha1($this->recipient->getUpdatedOn()->toMySQL() . ',' . $this->getNotificationsCollection()->getTimestampHash('created_on'));
    }

    /**
     * @var ModelCollection
     */
    private $notifications_collection;

    /**
     * Return assigned tasks collection.
     *
     * @return ModelCollection
     * @throws ImpossibleCollectionError
     */
    private function &getNotificationsCollection()
    {
        if (empty($this->notifications_collection)) {
            if ($this->recipient instanceof User && $this->getWhosAsking() instanceof User) {
                if ($this->unread_only) {
                    $this->notifications_collection = Notifications::prepareCollection('unread_notifications_for_recipient_' . $this->recipient->getId(), $this->getWhosAsking());
                } else {
                    $this->notifications_collection = Notifications::prepareCollection('notifications_for_recipient_' . $this->recipient->getId(), $this->getWhosAsking());
                }
            } else {
                throw new ImpossibleCollectionError("Invalid user and/or who's asking instance");
            }
        }

        return $this->notifications_collection;
    }
}
