<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait ITrackingObjectsImplementation
{
    /**
     * Return true if $user can track time or expenses in a given project.
     *
     * @return bool
     */
    public static function canTrackForOthers(User $user, Project $in_project)
    {
        return $user->isOwner() || $in_project->isLeader($user);
    }

    /**
     * Rebuild updated activities.
     */
    public static function rebuildUpdateActivites()
    {
        if ($modifications = DB::execute('SELECT DISTINCT l.id, l.parent_id, l.created_on, l.created_by_id, l.created_by_name, l.created_by_email FROM modification_logs AS l LEFT JOIN modification_log_values AS lv ON l.id = lv.modification_id WHERE l.parent_type = ? AND lv.field IN (?)', static::getInstanceClassName(), static::whatIsWorthRemembering())) {
            $ids = [];
            $modification_ids = [];

            foreach ($modifications as $modification) {
                $modification_ids[] = $modification['id'];

                if (!in_array($modification['parent_id'], $ids)) {
                    $ids[] = $modification['parent_id'];
                }
            }

            $object_modifications = ActivityLogs::prepareFieldValuesForSerialization(
                $modification_ids,
                self::whatIsWorthRemembering()
            );
            $object_paths = static::getParentPathsByElementIds($ids);

            $batch = new DBBatchInsert(
                'activity_logs',
                [
                    'type',
                    'parent_type',
                    'parent_id',
                    'parent_path',
                    'created_on',
                    'created_by_id',
                    'created_by_name',
                    'created_by_email',
                    'raw_additional_properties',
                ]
            );

            foreach ($modifications as $modification) {
                $batch->insertArray(
                    [
                        'type' => TrackingObjectUpdatedActivityLog::class,
                        'parent_type' => static::getInstanceClassName(),
                        'parent_id' => $modification['parent_id'],
                        'parent_path' => isset($object_paths[$modification['parent_id']])
                            ? $object_paths[$modification['parent_id']]
                            : '',
                        'created_on' => $modification['created_on'],
                        'created_by_id' => $modification['created_by_id'],
                        'created_by_name' => $modification['created_by_name'],
                        'created_by_email' => $modification['created_by_email'],
                        'raw_additional_properties' => serialize(
                            [
                                'modifications' => $object_modifications[$modification['id']],
                            ]
                        ),
                    ]
                );
            }

            $batch->done();
        }
    }

    public static function whatIsWorthRemembering(): array
    {
        return [
            'value',
            'is_trashed',
        ];
    }

    /**
     * Get parent paths by object ID-s.
     *
     * @return array
     */
    public static function getParentPathsByElementIds(array $ids)
    {
        $result = [];

        if (count($ids)) {
            $hidden_task_ids = [];
            $task_project_map = [];

            if ($task_rows = DB::execute('SELECT t.id, t.project_id, t.is_hidden_from_clients FROM tasks AS t LEFT JOIN ' . static::getTableName() . ' AS tr ON t.id = tr.parent_id WHERE tr.id IN (?) AND tr.parent_type = ?', $ids, 'Task')) {
                foreach ($task_rows as $task_row) {
                    $task_project_map[$task_row['id']] = $task_row['project_id'];

                    if ($task_row['is_hidden_from_clients']) {
                        $hidden_task_ids[] = $task_row['id'];
                    }
                }
            }

            $hidden_project_ids = [];
            if ($projects_rows = DB::execute('SELECT p.id, p.is_client_reporting_enabled FROM projects AS p LEFT JOIN ' . static::getTableName() . ' AS tr ON p.id = tr.parent_id WHERE tr.id IN (?) AND tr.parent_type = ?', $ids, 'Project')) {
                foreach ($projects_rows as $project_row) {
                    if (!$project_row['is_client_reporting_enabled']) {
                        $hidden_project_ids[] = $project_row['id'];
                    }
                }
            }

            $domain = str_replace('_', '-', static::getModelName(true));

            foreach (DB::execute('SELECT id, parent_type, parent_id FROM ' . static::getTableName() . ' WHERE id IN (?)', $ids) as $row) {
                if ($row['parent_type'] === 'Task') {
                    $task_id = $row['parent_id'];
                    $project_id = isset($task_project_map[$task_id]) ? $task_project_map[$task_id] : 0;

                    if (in_array($task_id, $hidden_task_ids)) {
                        $result[$row['id']] = "projects/$project_id/hidden-from-clients/$domain/$row[id]";
                    } else {
                        $result[$row['id']] = "projects/$project_id/visible-to-client/$domain/$row[id]";
                    }
                } elseif ($row['parent_type'] === 'Project') {
                    $project_id = $row['parent_id'];

                    if (in_array($project_id, $hidden_project_ids)) {
                        $result[$row['id']] = "projects/$project_id/hidden-from-clients/$domain/$row[id]";
                    } else {
                        $result[$row['id']] = "projects/$project_id/visible-to-client/$domain/$row[id]";
                    }
                } else {
                    $result[$row['id']] = "projects/$row[parent_id]/visible-to-client/$domain/$row[id]";
                }
            }
        }

        return $result;
    }
}
