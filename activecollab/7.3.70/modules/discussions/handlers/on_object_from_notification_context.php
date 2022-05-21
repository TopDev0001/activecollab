<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Discussions module on_object_from_notification_context events handler.
 *
 * @package activeCollab.modules.discussions
 * @subpackage handlers
 */

/**
 * @param null   $object
 * @param string $name
 * @param int    $id
 */
function discussions_handle_on_object_from_notification_context(&$object, $name, $id)
{
    if ($name == 'discussion') {
        $object = DataObjectPool::get('Discussion', $id);
    }
}
