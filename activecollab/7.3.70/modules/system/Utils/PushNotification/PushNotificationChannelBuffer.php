<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\PushNotification;

class PushNotificationChannelBuffer
{
    private int $user_id;

    private array $payload;

    private int $notification_id;

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): PushNotificationChannelBuffer
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): PushNotificationChannelBuffer
    {
        $this->payload = $payload;

        return $this;
    }

    public function getNotificationId(): int
    {
        return $this->notification_id;
    }

    public function setNotificationId(int $notification_id): PushNotificationChannelBuffer
    {
        $this->notification_id = $notification_id;

        return $this;
    }

}
