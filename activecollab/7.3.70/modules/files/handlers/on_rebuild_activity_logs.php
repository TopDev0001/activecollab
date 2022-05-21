<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Files module on_rebuild_activity_logs event handler implementation.
 *
 * @package activeCollab.modules.files
 * @subpackage handlers
 */

/**
 * @param Angie\NamedList $actions
 */
function files_handle_on_rebuild_activity_logs(&$actions)
{
    $actions->add('rebuild_files', [
        'label' => 'Rebuild file log entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "File" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/visible-to-clients/files/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM files WHERE is_hidden_from_clients = ?', false);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "File" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/hidden-from-clients/files/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM files WHERE is_hidden_from_clients = ?', true);
        },
    ]);

    $actions->add('rebuild_file_modifications', [
        'label' => 'Rebuild file update entries',
        'callback' => ['Files', 'rebuildUpdateActivites'],
    ]);

    $actions->add('rebuild_file_comments', [
        'label' => 'Rebuild file comment log entries',
        'callback' => function () {
            Comments::rebuildCommentCreatedParentPathForParentType('File');
        },
    ]);
}
