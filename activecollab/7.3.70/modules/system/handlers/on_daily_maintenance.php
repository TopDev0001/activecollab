<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

function system_handle_on_daily_maintenance()
{
    Notifications::cleanUp();

    ApiSubscriptions::deleteExpired();
    UserSessions::deleteExpired();
    UserInvitations::cleanUp();

    AngieApplication::securityLog()->cleanUp();

    (new LocalToWarehouseMover())->moveFilesToWarehouse();
}
