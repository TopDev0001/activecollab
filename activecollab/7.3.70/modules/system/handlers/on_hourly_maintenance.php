<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationEmailNotificationsUpdaterInterface;

function system_handle_on_hourly_maintenance()
{
    AngieApplication::getContainer()
        ->get(ConversationEmailNotificationsUpdaterInterface::class)
        ->update(
            DateTimeValue::makeFromTimestamp(
                AngieApplication::currentTimestamp()->getCurrentTimestamp()
            )
        );
}
