<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use IUser;
use Notification;
use User;

interface PushNotificationServiceInterface
{
    public function subscribe(User $user, array $payload): void;

    public function unsubscribe(User $user, string $unique_key): void;

    public function getPayload(Notification $notification, IUser $recipient): array;

    public function getManager(): UserDeviceManagerInterface;
}
