<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\NamedList;

function system_handle_on_rebuild_activity_logs(NamedList &$actions)
{
    $actions->add(
        'rebuild_system',
        [
            'label' => 'Rebuild project, company and team entries',
            'callback' => function () {
                DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Project" AS parent_type, id AS "parent_id", CONCAT("projects/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM projects');
                DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Company" AS parent_type, id AS "parent_id", CONCAT("companies/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM companies WHERE id > ?', 1);
                DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Team" AS parent_type, id AS "parent_id", CONCAT("teams/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM teams');
            },
        ]
    );

    $actions->add(
        'rebuild_first_owner',
        [
            'label' => 'Rebuild user account entries',
            'callback' => function () {
                DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", type AS parent_type, id AS "parent_id", CONCAT("users/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM users WHERE id > ?', 1);
            },
        ]
    );

    $actions->add(
        'rebuild_project_modifications',
        [
            'label' => 'Rebuild project update entries',
            'callback' => ['Projects', 'rebuildUpdateActivites'],
        ]
    );

    $actions->add(
        'rebuild_comments',
        [
            'label' => 'Rebuild comment entries',
            'callback' => function () {
                DB::execute(
                    'INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "CommentCreatedActivityLog" AS "type", parent_type, parent_id, "" AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, CONCAT("a:1:{s:10:\"comment_id\";i:", id, ";}") AS "raw_additional_properties" FROM comments'
                );
            },
        ]
    );
}
