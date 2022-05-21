<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @param null   $object
 * @param string $name
 * @param int    $id
 */
function tasks_handle_on_object_from_notification_context(&$object, $name, $id)
{
    if ($name === 'task') {
        $object = DataObjectPool::get('Task', $id);
    } elseif ($name === 'task-list') {
        $object = DataObjectPool::get('TaskList', $id);
    }
}
