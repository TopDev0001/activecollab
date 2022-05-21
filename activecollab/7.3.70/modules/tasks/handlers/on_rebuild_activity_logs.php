<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Tasks module on_rebuild_activity_logs event handler implementation.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage handlers
 */

/**
 * @param Angie\NamedList $actions
 */
function tasks_handle_on_rebuild_activity_logs(&$actions)
{
    $actions->add('rebuild_tasks', [
        'label' => 'Rebuild task entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Task" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/visible-to-clients/tasks/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM tasks WHERE is_hidden_from_clients = ?', false);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Task" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/hidden-from-clients/tasks/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM tasks WHERE is_hidden_from_clients = ?', true);
        },
    ]);

    $actions->add('rebuild_task_modifications', [
        'label' => 'Rebuild task update entries',
        'callback' => ['Tasks', 'rebuildUpdateActivites'],
    ]);

    $actions->add('rebuild_task_comments', [
        'label' => 'Rebuild task comment log entries',
        'callback' => function () {
            Comments::rebuildCommentCreatedParentPathForParentType('Task');
        },
    ]);

    $actions->add('rebuild_subtasks', [
        'label' => 'Rebuild subtask entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "SubtaskCreatedActivityLog" AS "type", "Task" AS parent_type, t.id AS "parent_id", CONCAT("projects/", t.project_id, "/visible-to-clients/tasks/", t.id) AS "parent_path", s.created_on, s.created_by_id, s.created_by_name, s.created_by_email, CONCAT("a:1:{s:10:\"subtask_id\";i:", s.id, ";}") AS "raw_additional_properties" FROM subtasks AS s LEFT JOIN tasks AS t ON s.task_id = t.id WHERE t.is_hidden_from_clients = ?', false);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "SubtaskCreatedActivityLog" AS "type", "Task" AS parent_type, t.id AS "parent_id", CONCAT("projects/", t.project_id, "/hidden-from-clients/tasks/", t.id) AS "parent_path", s.created_on, s.created_by_id, s.created_by_name, s.created_by_email, CONCAT("a:1:{s:10:\"subtask_id\";i:", s.id, ";}") AS "raw_additional_properties" FROM subtasks AS s LEFT JOIN tasks AS t ON s.task_id = t.id WHERE t.is_hidden_from_clients = ?', true);
        },
    ]);

    $actions->add('rebuild_subtask_modifications', [
        'label' => 'Rebuild subtask update entries',
        'callback' => ['Subtasks', 'rebuildUpdateActivites'],
    ]);

    $actions->add('rebuild_task_lists', [
        'label' => 'Rebuild task list entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "TaskList" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/visible-to-clients/task-lists/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM task_lists');
        },
    ]);

    $actions->add('rebuild_task_lists_modifications', [
        'label' => 'Rebuild task lists update entries',
        'callback' => ['TaskLists', 'rebuildUpdateActivites'],
    ]);
}
