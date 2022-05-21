<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_notification_inspector event handler implementation.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage handlers
 */

/**
 * Handle on_notification_inspector event.
 *
 * @param IProjectElement $context
 * @param mixed           $subcontext
 * @param IUser           $recipient
 * @param Language        $language
 * @param Angie\NamedList $properties
 * @param string          $link_style
 */
function tasks_handle_on_notification_inspector(&$context, &$subcontext, &$recipient, &$language, &$properties, $link_style)
{
    if ($context instanceof Task) {
        $task_list = $context->getTaskList();

        if ($task_list instanceof TaskList) {
            $properties->addAfter('task_list', [
                'label' => lang('Task List', null, null, $language),
                'value' => clean($task_list->getName()),
            ], 'project');
        }

        if ($subcontext instanceof Subtask) {
            $properties->addAfter('task', [
                'label' => lang('Task', null, null, $language),
                'value' => clean($subcontext->getTask()->getName()),
            ], ($task_list ? 'task_list' : 'project'));
        }
    }
}
