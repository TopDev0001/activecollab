<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use DB;
use User;
use UserDevice;
use UserDevices;

class UserDeviceManager implements UserDeviceManagerInterface
{
    public function findDeviceByUserIdAndUniqueKey(int $user_id, string $unique_key): ?UserDevice
    {
        return UserDevices::findOneBy([
            'user_id' => $user_id,
            'unique_key' => $unique_key,
        ]);
    }

    public function createDeviceForUser(User $user, array $payload): UserDevice
    {
        $payload['user_id'] = $user->getId();

        return UserDevices::create($payload);
    }

    public function getUnreadNotificationsCountForUserId(int $user_id): int
    {
        return (int) DB::executeFirstCell(
            "SELECT COUNT(DISTINCT n.parent_type, n.parent_id) AS 'row_count'
            FROM notifications AS n
            LEFT JOIN notification_recipients AS nr ON n.id = nr.notification_id
            WHERE nr.recipient_id = ? AND nr.read_on IS NULL",
            $user_id
        );
    }

    public function findDeviceTokensByUserIds(array $user_ids): array
    {
        return (array) DB::executeFirstColumn(
            'SELECT token FROM user_devices WHERE user_id IN (?)',
            $user_ids
        );
    }

    public function findDeviceTokensIndexedByUserIds(array $user_ids): array
    {
        $result = DB::execute(
            'SELECT user_id, token FROM user_devices WHERE user_id IN (?)',
            $user_ids
        );

        return $result ? $result->toArrayIndexedBy('user_id') : [];
    }
}
