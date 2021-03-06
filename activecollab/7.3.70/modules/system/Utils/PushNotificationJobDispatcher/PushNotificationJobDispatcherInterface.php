<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotificationJobDispatcher;

interface PushNotificationJobDispatcherInterface
{
    public function dispatchForUsers(
        array $user_ids,
        string $title,
        string $body,
        array $data = [],
        int $badge = null
    ): void;
}
