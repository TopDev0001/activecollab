<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Trash;

function environment_handle_on_daily_maintenance()
{
    UploadedFiles::cleanUp();

    if ($owner = Users::findFirstOwner()) {
        Trash::emptyTrash($owner, null, DateTimeValue::makeFromString('-30 days'));
    }

    SystemNotifications::toggle();

    AngieApplication::jobs()->getQueue()->cleanUp();
}
