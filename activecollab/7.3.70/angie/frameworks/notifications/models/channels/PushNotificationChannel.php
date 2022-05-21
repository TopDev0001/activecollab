<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationScheduleMatcherInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationServiceInterface;
use ActiveCollab\Module\System\Utils\PushNotificationJobDispatcher\PushNotificationJobDispatcherInterface;
use ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler\UsersBadgeCountThrottlerInterface;
use Angie\Notifications\PushNotificationInterface;

class PushNotificationChannel extends NotificationChannel
{
    public const CHANNEL_NAME = 'push';

    private PushNotificationServiceInterface $service;
    private PushNotificationScheduleMatcherInterface $matcher;
    private array $buffer = [];

    public function __construct(PushNotificationServiceInterface $service, PushNotificationScheduleMatcherInterface $matcher)
    {
        $this->service = $service;
        $this->matcher = $matcher;
    }

    public function getShortName(): string
    {
        return self::CHANNEL_NAME;
    }

    public function getVerboseName()
    {
        return 'Push Notification Channel';
    }

    public function isEnabledByDefault()
    {
        return true;
    }

    public function isEnabledFor(User $user): bool
    {
        if (AngieApplication::isOnDemand()) {
            return true;
        }

        return false;
    }

    public function send(
        Notification &$notification,
        IUser $recipient,
        bool $skip_sending_queue = false
    ) {
        if ($notification instanceof PushNotificationInterface) {
            $this->buffer[$notification->getId()]['user_ids'][] = $recipient->getId();
            if (!isset($this->buffer[$notification->getId()]['payload'])) {
                $this->buffer[$notification->getId()]['payload'] = $this->service->getPayload($notification, $recipient);
            }
        }
    }

    public function close($sending_interupted = false)
    {
        if (!$sending_interupted) {
            foreach ($this->buffer as $key => $value) {
                $user_ids = $value['user_ids'];
                $payload = $value['payload'];
                $matched_user_ids = $this->matcher->match($user_ids);
                if (count($matched_user_ids) > 0){
                    AngieApplication::getContainer()
                        ->get(PushNotificationJobDispatcherInterface::class)
                        ->dispatchForUsers(
                            $matched_user_ids,
                            $payload['title'],
                            $payload['body'],
                            $payload['data'],
                        );
                }
                if (count($user_ids) > 0) {
                    AngieApplication::getContainer()
                        ->get(UsersBadgeCountThrottlerInterface::class)
                        ->throttle($user_ids);
                }
            }
        }
    }

    public function open()
    {
        $this->buffer = [];
    }
}
