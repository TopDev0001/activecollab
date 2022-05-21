<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use User;
use UserDevice;

interface UserDeviceManagerInterface
{
    public function findDeviceByUserIdAndUniqueKey(int $user_id, string $unique_key): ?UserDevice;

    public function createDeviceForUser(User $user, array $payload): UserDevice;

    public function getUnreadNotificationsCountForUserId(int $user_id): int;

    public function findDeviceTokensByUserIds(array $user_ids): array;

    public function findDeviceTokensIndexedByUserIds(array $user_ids): array;
}
