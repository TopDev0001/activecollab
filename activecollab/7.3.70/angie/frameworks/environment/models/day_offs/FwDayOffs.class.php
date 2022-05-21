<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class FwDayOffs extends BaseDayOffs
{
    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }
}
