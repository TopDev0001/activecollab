<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Storage\StorageOverusedNotifier;

interface StorageOverusedNotifierInterface
{
    const LAST_NOTIFICATION_FOR_STORAGE_OVERUSED_MEMORY_KEY = 'recurring_task_not_created_due_to_disk_space';

    public function notifyAdministrators();
}
