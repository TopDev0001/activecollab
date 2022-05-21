<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use Angie\Modules\AngieFramework;

class NotificationsFramework extends AngieFramework
{
    const NAME = 'notifications';
    const PATH = __DIR__;

    protected string $name = 'notifications';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            Notification::class,
            function (array $ids): ?iterable
            {
                return Notifications::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                UserNotificationsCollection::class => __DIR__ . '/models/UserNotificationsCollection.php',
                FwUserObjectUpdatesCollection::class => __DIR__ . '/models/FwUserObjectUpdatesCollection.php',

                NotificationChannel::class => __DIR__ . '/models/channels/NotificationChannel.php',
                WebInterfaceNotificationChannel::class => __DIR__ . '/models/channels/WebInterfaceNotificationChannel.php',
                RealTimeNotificationChannel::class => __DIR__ . '/models/channels/RealTimeNotificationChannel.php',
                PushNotificationChannel::class => __DIR__.'/models/channels/PushNotificationChannel.php',

                INewInstanceUpdate::class => __DIR__ . '/models/INewInstanceUpdate.php',
            ]
        );
    }
}
