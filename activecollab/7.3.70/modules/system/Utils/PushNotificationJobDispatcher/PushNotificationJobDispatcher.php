<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotificationJobDispatcher;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\Job;
use ActiveCollab\ActiveCollabJobs\Jobs\Push\PushNotificationJob;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationPayloadTransformer;
use ActiveCollab\Module\System\Utils\PushNotification\UserDeviceManagerInterface;
use PushNotificationChannel;

class PushNotificationJobDispatcher implements PushNotificationJobDispatcherInterface
{
    private UserDeviceManagerInterface $device_manager;
    private JobsDispatcherInterface $jobs_dispatcher;
    private AccountIdResolverInterface $account_id_resolver;

    public function __construct(
        UserDeviceManagerInterface $device_manager,
        JobsDispatcherInterface $jobs_dispatcher,
        AccountIdResolverInterface $account_id_resolver
    ) {
        $this->device_manager = $device_manager;
        $this->jobs_dispatcher = $jobs_dispatcher;
        $this->account_id_resolver = $account_id_resolver;
    }

    public function dispatchForUsers(
        array $user_ids,
        string $title,
        string $body,
        array $data = [],
        int $badge = null
    ): void
    {
        $device_tokens = $this->device_manager->findDeviceTokensByUserIds($user_ids);

        if (!count($device_tokens)) {
            return;
        }

        $data = [
            'device_tokens' => $device_tokens,
            'title' => $title,
            'body' => PushNotificationPayloadTransformer::createExcerpt($body, ''),
            'instance_id' => $this->account_id_resolver->getAccountId(),
            'priority' => Job::HAS_PRIORITY,
            'attempts' => 1,
            'data' => $data,
        ];

        if (isset($badge)) {
            $data = array_merge(
                $data,
                ['badge' => $badge]
            );
        }

        $this->jobs_dispatcher->dispatch(
            new PushNotificationJob($data),
            PushNotificationChannel::CHANNEL_NAME
        );
    }
}
