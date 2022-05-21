<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\UserBadgeCountNotifier;

use ActiveCollab\ActiveCollabJobs\Jobs\Push\SilentPushNotificationJob;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\JobsQueue\Jobs\JobInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Module\System\Utils\PushNotification\UserDeviceManagerInterface;
use PushNotificationChannel;
use User;

class UserBadgeCountNotifier implements UserBadgeCountNotifierInterface
{
    private UserDeviceManagerInterface $user_device_manager;
    private $unread_messages_count_resolver;
    private JobsDispatcherInterface $jobs_dispatcher;
    private AccountIdResolverInterface $account_id_resolver;

    public function __construct(
        UserDeviceManagerInterface $user_device_manager,
        callable $unread_messages_count_resolver,
        JobsDispatcherInterface $jobs_dispatcher,
        AccountIdResolverInterface $account_id_resolver
    )
    {
        $this->user_device_manager = $user_device_manager;
        $this->unread_messages_count_resolver = $unread_messages_count_resolver;
        $this->jobs_dispatcher = $jobs_dispatcher;
        $this->account_id_resolver = $account_id_resolver;
    }

    public function notify(User $user): void
    {
        $device_tokens = $this->user_device_manager->findDeviceTokensByUserIds([$user->getId()]);

        if (!$device_tokens) {
            return;
        }

        $notifications_count = $this->user_device_manager->getUnreadNotificationsCountForUserId($user->getId());
        $messages_count = call_user_func($this->unread_messages_count_resolver, $user);

        $this->jobs_dispatcher->dispatch(
            new SilentPushNotificationJob([
                'instance_id' => $this->account_id_resolver->getAccountId(),
                'priority' => JobInterface::HAS_PRIORITY,
                'device_tokens' => $device_tokens,
                'badge' => $notifications_count + $messages_count,
            ]),
            PushNotificationChannel::CHANNEL_NAME
        );
    }
}
