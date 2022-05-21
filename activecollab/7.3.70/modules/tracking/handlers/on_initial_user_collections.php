<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_initial_user_collections event handler.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage handlers
 */

/**
 * @param array     $collections
 * @param User|null $user
 */
function tracking_handle_on_initial_user_collections(array &$collections, $user)
{
    $collections['job_types'] = $user ? JobTypes::prepareCollection('all_for_' . $user->getId(), $user) : [];
}
