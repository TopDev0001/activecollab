<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Handle on_history_field_renderers event.
 *
 * @package ActiveCollab.modules.system
 * @subpackage handlers
 */

/**
 * Get history changes as log text.
 *
 * @param ApplicationObject $object
 * @param array             $renderers
 */
function system_handle_on_history_field_renderers($object, array &$renderers)
{
    if ($object instanceof IProjectElement) {
        $renderers['project_id'] = function ($old_value, $new_value, Language $language) {
            $new_project = DataObjectPool::get('Project', $new_value);
            $old_project = DataObjectPool::get('Project', $old_value);

            if ($new_project instanceof Project) {
                if ($old_project instanceof Project) {
                    return lang('Project changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_project->getName(), 'new_value' => $new_project->getName()], true, $language);
                } else {
                    return lang('Project set to <b>:new_value</b>', ['new_value' => $new_project->getName()], true, $language);
                }
            } else {
                if ($old_project instanceof TaskList || is_null($new_project)) {
                    return lang('Project set to empty value', null, true, $language);
                }
            }
        };
    }
}
