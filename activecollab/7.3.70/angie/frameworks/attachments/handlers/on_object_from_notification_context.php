<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Attachments module on_object_from_notification_context events handler.
 *
 * @package angie.frameworks.attachments
 * @subpackage handlers
 */

/**
 * @param null   $object
 * @param string $name
 * @param int    $id
 */
function attachments_handle_on_object_from_notification_context(&$object, $name, $id)
{
    if ($name == 'file') {
        $object = DataObjectPool::get('File', $id);
    }
}
