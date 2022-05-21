<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_visible_object_paths event handler.
 *
 * @package angie.frameworks.authentication
 * @subpackage handlers
 */

/**
 * @param User                   $user
 * @param array                  $contexts
 * @param array                  $ignore_contexts
 * @param ApplicationObject|null $in
 */
function calendars_handle_on_visible_object_paths(User $user, array &$contexts, array &$ignore_contexts, &$in)
{
    if (empty($in)) {
        $contexts['calendars/*'] = Calendars::findIdsByUser($user);
    }
}
