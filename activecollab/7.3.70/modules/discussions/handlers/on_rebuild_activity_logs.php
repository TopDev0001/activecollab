<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Discussions module on_rebuild_activity_logs event handler implementation.
 *
 * @package activeCollab.modules.discussions
 * @subpackage handlers
 */

/**
 * @param Angie\NamedList $actions
 */
function discussions_handle_on_rebuild_activity_logs(&$actions)
{
    $actions->add('rebuild_discussions', [
        'label' => 'Rebuild discussion log entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Discussion" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/visible-to-clients/dicussions/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM discussions WHERE is_hidden_from_clients = ?', false);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Discussion" AS parent_type, id AS "parent_id", CONCAT("projects/", project_id, "/hidden-from-clients/discussions/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM discussions WHERE is_hidden_from_clients = ?', true);
        },
    ]);

    $actions->add('rebuild_discussion_modifications', [
        'label' => 'Rebuild discussion update entries',
        'callback' => ['Discussions', 'rebuildUpdateActivites'],
    ]);

    $actions->add('rebuild_discussion_comments', [
        'label' => 'Rebuild discussion comment log entries',
        'callback' => function () {
            Comments::rebuildCommentCreatedParentPathForParentType('Discussion');
        },
    ]);
}
