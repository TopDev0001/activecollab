<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_visible_object_paths event handler.
 *
 * @package activeCollab.modules.tracking
 * @subpackage handlers
 */

/**
 * @param User                   $user
 * @param array                  $contexts
 * @param array                  $ignore_contexts
 * @param ApplicationObject|null $in
 */
function tracking_handle_on_visible_object_paths(User $user, array &$contexts, array &$ignore_contexts, &$in)
{
    if ($in instanceof Project && ($user->isOwner() || $in->isMember($user))) {
        if ($user instanceof Client) {
            if ($in->getIsClientReportingEnabled()) {
                $contexts["projects/{$in->getId()}/visible-to-clients/time-records/*"] = true;
                $contexts["projects/{$in->getId()}/visible-to-clients/expenses/*"] = true;
            } else {
                $ignore_contexts["projects/{$in->getId()}/visible-to-clients/time-records/*"] = true;
                $ignore_contexts["projects/{$in->getId()}/visible-to-clients/expenses/*"] = true;
            }
        } else {
            if (!$user instanceof Owner) {
                $time_record_ids = DB::executeFirstColumn('SELECT id FROM time_records WHERE created_by_id != ?', $user->getId());
                $expense_ids = DB::executeFirstColumn('SELECT id FROM expenses WHERE created_by_id != ?', $user->getId());

                $ignore_contexts["projects/{$in->getId()}/visible-to-clients/time-records/*"] = $time_record_ids;
                $ignore_contexts["projects/{$in->getId()}/hidden-from-clients/time-records/*"] = $time_record_ids;
                $ignore_contexts["projects/{$in->getId()}/visible-to-clients/expenses/*"] = $expense_ids;
                $ignore_contexts["projects/{$in->getId()}/hidden-from-clients/expenses/*"] = $expense_ids;
            }
        }
    } elseif (empty($in)) {
        if (!$user->isOwner()) {
            $escaped_user_id = DB::escape($user->getId());

            // Get project ID-s where tracking is enabled
            $project_ids = DB::executeFirstColumn("SELECT id FROM projects INNER JOIN project_users ON projects.id = project_users.project_id WHERE projects.is_tracking_enabled = '1' AND projects.is_trashed = '0' AND project_users.user_id = $escaped_user_id");
            $projects = empty($project_ids) ? null : Projects::findByIds($project_ids);

            if ($projects) {
                /*
                 * Get time record or expense IDs mapped by project ID that were not tracked by the given user
                 *
                 * @param  string $table_name
                 * @param  string $escaped_project_ids
                 * @param  string $escaped_user_id
                 * @return array
                 */
                $ids_from_table = function ($table_name, $escaped_project_ids, $escaped_user_id) {
                    $ids = [];

                    // Query records tracked for project
                    if ($rows = DB::execute("SELECT parent_id AS 'project_id', GROUP_CONCAT(id SEPARATOR ',') AS 'ids' FROM $table_name WHERE (parent_type = 'Project' AND parent_id IN ($escaped_project_ids)) AND created_by_id != $escaped_user_id GROUP BY project_id")) {
                        foreach ($rows as $row) {
                            $ids[$row['project_id']] = array_map('intval', explode(',', $row['ids']));
                        }
                    }

                    // Query records tracked for project's tasks
                    if ($rows = DB::execute("
              SELECT t.project_id AS 'project_id', GROUP_CONCAT(tr.id SEPARATOR ',') AS 'ids'
              FROM $table_name AS tr INNER JOIN tasks AS t ON tr.parent_id = t.id
              WHERE tr.parent_type = 'Task' AND t.project_id IN ($escaped_project_ids) AND tr.created_by_id != $escaped_user_id
              GROUP BY t.project_id
            ")
                    ) {
                        foreach ($rows as $row) {
                            $project_id = $row['project_id'];

                            if (empty($ids[$project_id])) {
                                $ids[$project_id] = array_map('intval', explode(',', $row['ids']));
                            } else {
                                $ids[$project_id] = array_merge($ids[$project_id], array_map('intval', explode(',', $row['ids'])));
                            }
                        }
                    }

                    return $ids;
                };

                $escaped_project_ids = DB::escape($project_ids);

                $time_record_ids = $ids_from_table('time_records', $escaped_project_ids, $escaped_user_id);
                $expense_ids = $ids_from_table('expenses', $escaped_project_ids, $escaped_user_id);

                foreach ($projects as $project) {
                    $project_id = $project->getId();

                    if ($user instanceof Client) {
                        if ($project->getIsClientReportingEnabled()) {
                            $contexts["projects/{$project_id}/visible-to-clients/time-records/*"] = true;
                            $contexts["projects/{$project_id}/visible-to-clients/expenses/*"] = true;
                        } else {
                            $ignore_contexts["projects/{$project_id}/visible-to-clients/time-records/*"] = true;
                            $ignore_contexts["projects/{$project_id}/visible-to-clients/expenses/*"] = true;
                        }
                    } else {
                        if (isset($time_record_ids[$project_id])) {
                            $ignore_contexts["projects/{$project_id}/visible-to-clients/time-records/*"] = $time_record_ids[$project_id];
                            $ignore_contexts["projects/{$project_id}/hidden-from-clients/time-records/*"] = $time_record_ids[$project_id];
                        }

                        if (isset($expense_ids[$project_id])) {
                            $ignore_contexts["projects/{$project_id}/visible-to-clients/expenses/*"] = $expense_ids[$project_id];
                            $ignore_contexts["projects/{$project_id}/hidden-from-clients/expenses/*"] = $expense_ids[$project_id];
                        }
                    }
                }
            }
        }
    }
}
