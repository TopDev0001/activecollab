<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Handle on_history_field_renderers event.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage handlers
 */

/**
 * Get history changes as log text.
 *
 * @param Task|ApplicationObject $object
 * @param array                  $renderers
 */
function tasks_handle_on_history_field_renderers($object, array &$renderers)
{
    if ($object instanceof Task) {
        $renderers['task_list_id'] = function ($old_value, $new_value, Language $language) {
            $new_task_list = DataObjectPool::get('TaskList', $new_value);
            $old_task_list = DataObjectPool::get('TaskList', $old_value);

            if ($new_task_list instanceof TaskList) {
                if ($old_task_list instanceof TaskList) {
                    return lang('Task list changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_task_list->getName(), 'new_value' => $new_task_list->getName()], true, $language);
                } else {
                    return lang('Task list set to <b>:new_value</b>', ['new_value' => $new_task_list->getName()], true, $language);
                }
            } else {
                if ($old_task_list instanceof TaskList || is_null($new_task_list)) {
                    return lang('Task list set to empty value', null, true, $language);
                }
            }
        };
    }
}
