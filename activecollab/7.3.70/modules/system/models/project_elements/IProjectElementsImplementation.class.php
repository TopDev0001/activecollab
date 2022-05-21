<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IProjectElementsImplementation
{
    /**
     * @var array|bool
     */
    private static $preloaded_counts = false;

    /**
     * @var array|bool
     */
    private static $preloaded_size = false;

    /**
     * Preload counts for the given projects (to bring the number of queries down).
     *
     * @param int[] $project_ids
     * @param bool  $force_reload
     */
    public static function preloadCountByProject(array $project_ids, $force_reload = false)
    {
        if (self::$preloaded_counts === false || $force_reload) {
            self::$preloaded_counts = Projects::preloadProjectElementsCount($project_ids, static::getTableName());
        }
    }

    /**
     * Preload size for the given projects (to bring the number of queries down).
     *
     * @param  int[]             $project_ids
     * @throws InvalidParamError
     */
    public static function preloadFileSizeSumByProject(array $project_ids, bool $force_reload = false)
    {
        if (self::$preloaded_size === false || $force_reload) {
            $project_sizes = DB::execute('SELECT project_id, SUM(size) AS size FROM (
                                    SELECT project_id, SUM(size) AS size FROM attachments WHERE project_id IN (?) AND type IN (?) GROUP BY project_id UNION ALL
                                    SELECT project_id, SUM(size) AS size FROM files WHERE project_id IN (?) AND type IN (?) GROUP BY project_id
                                    ) AS project_size GROUP BY project_id', $project_ids, [LocalAttachment::class, WarehouseAttachment::class], $project_ids, [LocalFile::class, WarehouseFile::class]);

            if ($project_sizes) {
                foreach ($project_sizes as $project_size) {
                    self::$preloaded_size[$project_size['project_id']] = (int) $project_size['size'];
                }
            }
        }
    }

    /**
     * Reset manager state (between tests for example).
     */
    public static function resetState()
    {
        self::$preloaded_counts = [];
        self::$preloaded_size = [];
    }

    /**
     * Count elements by project.
     *
     * @return int
     */
    public static function countByProject(Project $project)
    {
        if (self::$preloaded_counts !== false) {
            return isset(self::$preloaded_counts[$project->getId()]) ? self::$preloaded_counts[$project->getId()] : 0;
        }

        return static::count(['project_id = ? AND is_trashed = ?', $project->getId(), false]);
    }

    public static function fileSizeSumByProject(Project $project): int
    {
        if (self::$preloaded_size !== false) {
            return isset(self::$preloaded_size[$project->getId()]) ? self::$preloaded_size[$project->getId()] : 0;
        }

        $project_size = DB::executeFirstCell('SELECT SUM(size) FROM (
                                    SELECT project_id, SUM(size) AS size FROM attachments WHERE project_id = ? AND type IN (?) GROUP BY project_id UNION ALL
                                    SELECT project_id, SUM(size) AS size FROM files WHERE project_id = ? AND type IN (?) GROUP BY project_id
                                    ) AS project_size', $project->getId(), [LocalAttachment::class, WarehouseAttachment::class], $project->getId(), [LocalFile::class, WarehouseFile::class]);

        return (int) $project_size;
    }

    /**
     * Delete objects by project.
     */
    public static function deleteByProject(Project $project)
    {
        $items = static::find(
            [
                'conditions' => [
                    'project_id = ?', $project->getId(),
                ],
            ]
        );

        if ($items) {
            foreach ($items as $item) {
                $item->delete();
            }
        }
    }

    /**
     * Automatically subscribe project leader when project element is created by a client.
     *
     * @param  IProjectElement|ICreatedBy|ISubscriptions $project_element
     * @return IProjectElement
     */
    public static function autoSubscribeProjectLeader(IProjectElement $project_element)
    {
        if ($project_element instanceof ISubscriptions && $project_element instanceof ICreatedBy) {
            $created_by = $project_element->getCreatedBy();

            if ($created_by instanceof User && $created_by->isClient()) {
                $project = $project_element->getProject();

                if ($project->getLeaderId() && !in_array($project->getLeaderId(), $project_element->getSubscriberIds())) {
                    $project_element->subscribe($project->getLeader());
                }
            }
        }

        return $project_element;
    }

    // ---------------------------------------------------
    //  Activity logs
    // ---------------------------------------------------

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
                $batch->insertArray([
                    'type' => InstanceUpdatedActivityLog::class,
                    'parent_type' => static::getInstanceClassName(),
                    'parent_id' => $modification['parent_id'],
                    'parent_path' => $object_paths[$modification['parent_id']] ?? '',
                    'created_on' => $modification['created_on'],
                    'created_by_id' => $modification['created_by_id'],
                    'created_by_name' => $modification['created_by_name'],
                    'created_by_email' => $modification['created_by_email'],
                    'raw_additional_properties' => serialize(
                        [
                            'modifications' => $object_modifications[$modification['id']],
                        ]
                    ),
                ]);
            }

            $batch->done();
        }
    }

    public static function whatIsWorthRemembering(): array
    {
        return [
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
            if (static::fieldExists('is_hidden_from_clients')) {
                $fields = 'id, project_id, is_hidden_from_clients';
            } else {
                $fields = "id, project_id, '0' AS is_hidden_from_clients";
            }

            if ($rows = DB::execute("SELECT $fields FROM " . static::getTableName() . ' WHERE id IN (?)', $ids)) {
                foreach ($rows as $row) {
                    $result[$row['id']] = 'projects/' . $row['project_id'] . '/' . ($row['is_hidden_from_clients'] ? 'hidden-from-clients' : 'visible-to-clients') . '/' . str_replace('_', '-', static::getModelName(true)) . '/' . $row['id'];
                }
            }
        }

        return $result;
    }
}
