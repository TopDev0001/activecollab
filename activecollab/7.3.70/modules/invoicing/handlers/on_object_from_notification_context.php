<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Invoicing module on_object_from_notification_context events handler.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * @param null   $object
 * @param string $name
 * @param int    $id
 */
function invoicing_handle_on_object_from_notification_context(&$object, $name, $id)
{
    if ($name == 'estimate') {
        $object = DataObjectPool::get('Estimate', $id);
    }
}
