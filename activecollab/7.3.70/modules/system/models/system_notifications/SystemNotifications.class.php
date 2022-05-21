<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class SystemNotifications extends BaseSystemNotifications
{
    public static function toggle()
    {
        if (AngieApplication::isOnDemand()) {
            if (DiskSpaceAlmostUsedSystemNotifications::shouldBeRaised()) {
                DiskSpaceAlmostUsedSystemNotifications::add();
            }

            if (DiskSpaceSystemNotifications::shouldBeRaised()) {
                DiskSpaceSystemNotifications::add();
            } else {
                DiskSpaceSystemNotifications::clearNotifications();
            }

            if (FreeTrialSystemNotifications::shouldBeRaised()) {
                FreeTrialSystemNotifications::add();
            } else {
                FreeTrialSystemNotifications::clearNotifications();
            }

            if (MembersExceededSystemNotifications::shouldBeRaised()) {
                MembersExceededSystemNotifications::add();
            } else {
                MembersExceededSystemNotifications::clearNotifications();
            }

            if (PaymentFailedSystemNotifications::shouldBeRaised()) {
                PaymentFailedSystemNotifications::add();
            } else {
                PaymentFailedSystemNotifications::clearNotifications();
            }

            if (SubscriptionCancelledSystemNotifications::shouldBeRaised()) {
                SubscriptionCancelledSystemNotifications::add();
            } else {
                SubscriptionCancelledSystemNotifications::clearNotifications();
            }
        } else {
            if (SupportExpirationSystemNotifications::shouldBeRaised()) {
                SupportExpirationSystemNotifications::add();
            } else {
                SupportExpirationSystemNotifications::clearNotifications();
            }

            if (UpgradeAvailableSystemNotifications::shouldBeRaised()) {
                UpgradeAvailableSystemNotifications::add();
            } else {
                UpgradeAvailableSystemNotifications::clearNotifications();
            }
        }
    }

    public static function clearNotifications()
    {
        parent::delete(['type = ?', static::getType()]);
    }

    /**
     * Return 'type' attribute for polymorh model creation.
     *
     * @return string
     */
    public static function getType()
    {
        throw new NotImplementedError(__METHOD__);
    }

    /**
     * Return new collection.
     *
     * @param  User|null                 $user
     * @return ModelCollection
     * @throws ImpossibleCollectionError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'active_recipient_system_notifications')) {
            $collection->setConditions('recipient_id = ? AND is_dismissed = ?', $user->getId(), 0);
        } elseif (str_starts_with($collection_name, 'all_recipient_system_notifications')) {
            $collection->setConditions('recipient_id = ?', $user->getId());
        }

        return $collection;
    }

    /**
     * Add this notification.
     *
     * @return bool
     */
    public static function add(Owner $user = null)
    {
        if ($user instanceof Owner) {
            $to_users = [$user];
        } else {
            $to_users = Users::findOwners();
        }

        foreach ($to_users as $user) {
            $system_notification = self::findByRecipientId($user->getId());

            if (!$system_notification) { //id doesn't exists
                $attributes = [
                    'recipient_id' => $user->getId(),
                ];
                self::create($attributes);
            } else {
                if ($system_notification instanceof SystemNotification && !$system_notification->isPermanent() && $system_notification->getIsDismissed()) { //if is dismissed and it should be shown again
                    $system_notification->setIsDismissed(false);
                    $system_notification->save();
                }
            }
        }

        return true;
    }

    /**
     * Find by recipient id.
     *
     * @param  int                     $recipient_id
     * @return SystemNotification|null
     */
    public static function findByRecipientId($recipient_id)
    {
        return self::findOne(
            [
                'conditions' => [
                    'type = ? AND recipient_id = ?',
                    static::getType(),
                    $recipient_id,
                ],
            ]
        );
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): SystemNotification
    {
        $attributes['type'] = static::getType();
        $attributes['created_on'] = new DateTimeValue();

        return parent::create($attributes, $save, $announce);
    }
}
