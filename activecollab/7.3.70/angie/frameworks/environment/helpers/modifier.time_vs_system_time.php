<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Utils\TimestampForUserFormatter;

/**
 * @param  DateTimeValue        $content
 * @param  User|null            $user
 * @return string
 * @throws InvalidInstanceError
 */
function smarty_modifier_time_vs_system_time($content, $user = null)
{
    if ($user === null) {
        $user = AngieApplication::authentication()->getAuthenticatedUser();
    }

    if ($content instanceof DateTimeValue) {
        return (new TimestampForUserFormatter($user))->formatTimestamp($content);
    } else {
        throw new InvalidInstanceError('content', $content, 'DateTimeValue');
    }
}
