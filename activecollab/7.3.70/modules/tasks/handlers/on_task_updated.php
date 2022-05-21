<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Utils\NotificationRecipientsCleaner\NotificationRecipientsCleanerInterface;

function tasks_handle_on_task_updated(Task $task, array $attributes)
{
    if (array_key_exists('moved_to_project', $attributes)) {
        AngieApplication::getContainer()->get(NotificationRecipientsCleanerInterface::class)->clean($task);
    }
}
