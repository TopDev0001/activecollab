<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Trash\Sections;

function tasks_handle_on_trash_sections(Sections &$sections, User $user)
{
    if ($user->isOwner()) {
        // get task lists
        $task_list_id_name_map = DB::executeIdNameMap(
            'SELECT tl.id, tl.name FROM task_lists AS tl
                INNER JOIN projects as p ON p.id = tl.project_id AND p.is_trashed = ?
                WHERE tl.is_trashed = ?
                ORDER BY tl.trashed_on DESC',
            false,
            true
        );

        // get tasks
        $task_id_name_map = DB::executeIdNameMap(
            'SELECT t.id, t.name FROM tasks AS t
                INNER JOIN task_lists AS tl ON tl.id = t.task_list_id AND tl.is_trashed = ?
                INNER JOIN projects AS p ON p.id = t.project_id AND p.is_trashed = ?
                WHERE t.is_trashed = ?
                ORDER BY t.trashed_on DESC',
            false,
            false,
            true
        );

        // get subtasks
        $subtask_id_name_map = DB::executeIdNameMap(
            'SELECT st.id, st.body AS "name" FROM subtasks AS st
                INNER JOIN tasks AS t ON t.id = st.task_id AND t.is_trashed = ?
                WHERE st.is_trashed = ?
                ORDER BY st.trashed_on DESC',
            false,
            true
        );

        // get recurring tasks
        $recurring_task_id_name_map = DB::executeIdNameMap(
            'SELECT rt.id, rt.name FROM recurring_tasks AS rt
                INNER JOIN task_lists AS tl ON tl.id = rt.task_list_id AND tl.is_trashed = ?
                WHERE rt.is_trashed = ?
                ORDER BY rt.trashed_on DESC',
            false,
            true
        );
    } elseif ($user->isMember() && $project_ids = $user->getProjectIds()) {
        // get task lists
        $task_list_id_name_map = DB::executeIdNameMap(
            'SELECT tl.id, tl.name FROM task_lists as tl
                INNER JOIN projects AS p ON p.id = tl.project_id AND p.is_trashed = ?
                WHERE tl.is_trashed = ? AND tl.trashed_by_id = ? AND tl.project_id IN (?)
                ORDER BY tl.trashed_on DESC',
            false,
            true,
            $user->getId(),
            $project_ids
        );

        // get tasks
        $task_id_name_map = DB::executeIdNameMap(
            'SELECT t.id, t.name FROM tasks as t
                INNER JOIN task_lists as tl ON tl.id = t.task_list_id AND tl.is_trashed = ?
                INNER JOIN projects as p ON p.id = t.project_id AND p.is_trashed = ?
                WHERE t.is_trashed = ? AND t.trashed_by_id = ? AND t.project_id IN (?)
                ORDER BY t.trashed_on DESC',
            false,
            false,
            true,
            $user->getId(),
            $project_ids
        );

        // get subtasks
        $subtask_id_name_map = DB::executeIdNameMap(
            'SELECT st.id, st.body AS "name" FROM subtasks AS st
                INNER JOIN tasks AS t ON t.id = st.task_id AND t.is_trashed = ?
                WHERE st.trashed_by_id = ? AND st.is_trashed = ?
                ORDER BY st.trashed_on DESC',
            false,
            $user->getId(),
            true
        );

        // get recurring tasks
        $recurring_task_id_name_map = DB::executeIdNameMap(
            'SELECT rt.id, rt.name FROM recurring_tasks AS rt
                INNER JOIN projects AS p ON p.id = rt.project_id AND p.is_trashed = ?
                WHERE rt.is_trashed = ?
                ORDER BY rt.trashed_on DESC',
            false,
            true
        );
    }

    if (!empty($task_list_id_name_map)) {
        $sections->registerTrashedObjects('TaskList', $task_list_id_name_map);
    }

    if (!empty($task_id_name_map)) {
        $sections->registerTrashedObjects('Task', $task_id_name_map);
    }

    if (!empty($recurring_task_id_name_map)) {
        $sections->registerTrashedObjects('RecurringTask', $recurring_task_id_name_map);
    }

    if (!empty($subtask_id_name_map)) {
        $sections->registerTrashedObjects('Subtask', $subtask_id_name_map, Sections::SECOND_WAVE);
    }
}
