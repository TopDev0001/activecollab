<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_rebuild_activity_logs event handler implementation.
 *
 * @package activeCollab.modules.notes
 * @subpackage handlers
 */

/**
 * @param Angie\NamedList $actions
 */
function notes_handle_on_rebuild_activity_logs(&$actions)
{
    $actions->add('rebuild_notes', [
        'label' => 'Rebuild note activity log entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Note" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/visible-to-clients/notes/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM notes WHERE is_hidden_from_clients = ?', false);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Note" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/hidden-from-clients/notes/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM notes WHERE is_hidden_from_clients = ?', true);
        },
    ]);

    $actions->add('rebuild_note_modifications', [
        'label' => 'Rebuild note update entries',
        'callback' => ['Notes', 'rebuildUpdateActivites'],
    ]);

    $actions->add('rebuild_note_comments', [
        'label' => 'Rebuild note comment log entries',
        'callback' => function () {
            Comments::rebuildCommentCreatedParentPathForParentType('Note');
        },
    ]);
}
