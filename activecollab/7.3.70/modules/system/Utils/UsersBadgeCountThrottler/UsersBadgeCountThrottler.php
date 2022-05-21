<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\SendUserBadgeCount;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Module\System\Utils\JobsThrottler\JobsThrottleInterface;
use ActiveCollab\Module\System\Utils\PushNotification\UserDeviceManagerInterface;
use PushNotificationChannel;

class UsersBadgeCountThrottler implements UsersBadgeCountThrottlerInterface
{
    private UserDeviceManagerInterface $device_manager;
    private JobsThrottleInterface $jobs_throttle;
    private AccountIdResolverInterface $account_id_resolver;

    public function __construct(
        UserDeviceManagerInterface $device_manager,
        JobsThrottleInterface $jobs_throttle,
        AccountIdResolverInterface $account_id_resolver
    ) {
        $this->device_manager = $device_manager;
        $this->jobs_throttle = $jobs_throttle;
        $this->account_id_resolver = $account_id_resolver;
    }

    public function throttle(array $user_ids): void
    {
        $device_tokens = $this->device_manager->findDeviceTokensIndexedByUserIds($user_ids);

        if (!count($device_tokens)) {
            return;
        }

        $account_id = $this->account_id_resolver->getAccountId();

        foreach ($user_ids as $user_id) {
            if (array_key_exists($user_id, $device_tokens)) {
                $this->jobs_throttle
                    ->throttle(
                        SendUserBadgeCount::class,
                        [
                            'instance_id' => $account_id,
                            'user_id' => $user_id,
                        ],
                        10,
                        true,
                        PushNotificationChannel::CHANNEL_NAME
                    );
            }
        }
    }
}
