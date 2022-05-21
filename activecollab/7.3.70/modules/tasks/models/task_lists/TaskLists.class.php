<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\EventListeners\WebhookDispatcherInterface;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListCreatedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListInsertedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListReorderedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListUpdatedEvent;

class TaskLists extends BaseTaskLists
{
    use IProjectElementsImplementation;

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): TaskList
    {
        $task_list = parent::create($attributes, $save, false);

        if ($announce) {
            DataObjectPool::announce(new TaskListCreatedEvent($task_list));
        }

        return $task_list;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): TaskList
    {
        $task_list = parent::update($instance, $attributes, $save);

        DataObjectPool::announce(new TaskListUpdatedEvent($task_list));

        return $task_list;
    }

    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'assignments_as_calendar_events')) {
            return self::prepareCalendarEventsCollection($collection_name, $user);
        } else {
            $collection = parent::prepareCollection($collection_name, $user);

            $collection->setPreExecuteCallback(function ($ids) {
                Tasks::preloadCountByTaskList($ids);
            });

            if (str_starts_with($collection_name, 'active_task_lists_in_project') || str_starts_with($collection_name, 'all_task_lists_in_project')) {
                self::prepareProjectTaskListsCollection($collection, $collection_name);
            } elseif (str_starts_with($collection_name, 'tasks_dependencies_suggestion')) {
                self::prepareTaskListSugestionCollectionByProjectAndTask($collection, $collection_name, $user);
            } else {
                if (str_starts_with($collection_name, 'archived_task_lists_in_project')) {
                    self::prepareArchivedProjectTaskListsCollection($collection, $collection_name);
                } else {
                    throw new InvalidParamError('collection_name', $collection_name);
                }
            }

            return $collection;
        }
    }

    /**
     * Prepare calendar events collection.
     *
     * @param  string          $collection_name
     * @param  User|null       $user
     * @return ModelCollection
     */
    private static function prepareCalendarEventsCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $to = array_pop($bits);
        $from = array_pop($bits);

        $additional_conditions = DB::prepare('is_trashed = ? AND start_on IS NOT NULL AND due_on IS NOT NULL AND ((start_on BETWEEN ? AND ?) OR (due_on BETWEEN ? AND ?) OR (start_on < ? AND due_on > ?))', false, $from, $to, $from, $to, $from, $to);

        // everything in all projects
        if (str_starts_with($collection_name, 'assignments_as_calendar_events_everything_in_all_projects')) {
            if ($user->isPowerUser()) {
                $collection = parent::prepareCollection($collection_name, $user);
                $collection->setConditions($additional_conditions);
            } else {
                throw new ImpossibleCollectionError('Only project managers can see everything in all projects');
            }

            // everything in my projects
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events_everything_in_my_projects')) {
            $project_ids = Projects::findIdsByUser($user, false, DB::prepare('is_trashed = ?', false));

            if ($project_ids && is_foreachable($project_ids)) {
                $collection = parent::prepareCollection($collection_name, $user);
                $collection->setConditions("project_id IN (?) AND $additional_conditions", $project_ids);
            } else {
                throw new ImpossibleCollectionError('User not involved in any of the projects');
            }

            // only my assignments - don't return task lists for my assignments
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events_only_my_assignments')) {
            $collection = parent::prepareCollection($collection_name, $user);
            $collection->setConditions('id = ?', 0);

            // assignments for specified user
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events')) {
            $assignee_id = array_pop($bits);
            $assignee = DataObjectPool::get('User', $assignee_id);

            if ($user->isPowerUser()) {
                if ($assignee instanceof User) {
                    $project_ids = Projects::findIdsByUser($assignee, true, DB::prepare('is_trashed = ?', false));

                    if ($project_ids && is_foreachable($project_ids)) {
                        $collection = parent::prepareCollection($collection_name, $assignee);
                        $collection->setConditions("project_id IN (?) AND $additional_conditions", $project_ids);
                    } else {
                        throw new ImpossibleCollectionError('User not involved in any of the projects');
                    }
                } else {
                    throw new ImpossibleCollectionError("Assignee #{$assignee_id} not found");
                }
            } else {
                throw new ImpossibleCollectionError('Only project managers can see assignments for specified user');
            }

            // invalid collection name
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
        }

        return $collection;
    }

    /**
     * Prepare project related collections.
     *
     * @param  string          $collection_name
     * @return ModelCollection
     */
    private static function prepareProjectTaskListsCollection(ModelCollection $collection, $collection_name)
    {
        $bits = explode('_', $collection_name);
        $project_id = array_pop($bits);
        $collection_name = implode('_', $bits);

        $project = DataObjectPool::get('Project', $project_id);

        if ($project instanceof Project) {
            switch ($collection_name) {
                case 'active_task_lists_in_project':
                    $collection->setConditions('project_id = ? AND completed_on IS NULL AND is_trashed = ?', $project_id, false);
                    break;
                case 'all_task_lists_in_project':
                    $collection->setConditions('project_id = ? AND is_trashed = ?', $project_id, false);
                    break;
                default:
                    throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
            }
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
        }
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Prepare project related collections.
     *
     * @param  string          $collection_name
     * @return ModelCollection
     */
    private static function prepareArchivedProjectTaskListsCollection(ModelCollection &$collection, $collection_name)
    {
        $bits = explode('_', $collection_name);

        $page = (int) array_pop($bits);
        array_pop($bits); // _page_

        $project = DataObjectPool::get('Project', array_pop($bits));

        if ($project instanceof Project) {
            $collection->setConditions('project_id = ? AND completed_on IS NOT NULL AND is_trashed = ?', $project->getId(), false);
            $collection->setOrderBy('completed_on DESC');
            $collection->setPagination($page, 30);
        } else {
            throw new ImpossibleCollectionError('Project not found');
        }
    }

    private static function prepareTaskListSugestionCollectionByProjectAndTask(
        ModelCollection &$collection,
        $collection_name,
        User $user
    )
    {
        $bits = explode('_', $collection_name);

        $task_id = (int) array_pop($bits);
        array_pop($bits); // _task_
        $project_id = array_pop($bits);

        $condition = $user->isClient()
            ? DB::prepare('t.completed_on IS NULL AND t.is_trashed = ? AND t.is_hidden_from_clients = ?', false, false)
            : DB::prepare('t.completed_on IS NULL AND t.is_trashed = ?', false);

        $sugestion_task_ids = DB::executeFirstColumn(
            "SELECT t.id
            FROM tasks t
            WHERE t.id != ? AND $condition AND t.project_id = ? AND t.id NOT IN
            (
              SELECT td1.child_id
              FROM task_dependencies td1
              WHERE td1.parent_id = ?
            ) AND t.id NOT IN
            (
              SELECT td2.parent_id
              FROM task_dependencies td2
              WHERE td2.child_id = ?
            )",
            $task_id,
            $project_id,
            $task_id,
            $task_id
        );

        $task_list_ids = DB::executeFirstColumn(
            'SELECT task_list_id FROM tasks WHERE id IN (?) GROUP BY task_list_id',
            $sugestion_task_ids
        );

        $collection->setConditions('id IN (?)', $task_list_ids);
    }

    /**
     * Returns true if $user can add task lists to $project.
     *
     * @return bool
     */
    public static function canAdd(IUser $user, Project $project)
    {
        if ($user instanceof User) {
            return ($user->isPowerUser() || $project->isLeader($user) || $project->isMember($user)) && !$user->isClient();
        }

        return false;
    }

    /**
     * Returns true if $user can reorder task lists in $project.
     *
     * @return bool
     */
    public static function canReorder(IUser $user, Project $project)
    {
        if ($user instanceof User) {
            return $user->isPowerUser() || $project->isLeader($user) || $project->isMember($user);
        }

        return false;
    }

    /**
     * Preload counts for the given projects (to bring the number of queries down).
     *
     * @param int[] $project_ids
     * @param bool  $force_reload
     */
    public static function preloadCountByProject(array $project_ids, $force_reload = false)
    {
        if (self::$preloaded_counts === false || $force_reload) {
            self::$preloaded_counts = [];

            if ($rows = DB::execute("SELECT project_id, COUNT('id') AS 'row_count' FROM task_lists WHERE completed_on IS NULL AND is_trashed = ? AND project_id IN (?) GROUP BY project_id", false, $project_ids)) {
                foreach ($rows as $row) {
                    self::$preloaded_counts[$row['project_id']] = (int) $row['row_count'];
                }
            }
        }
    }

    public static function resetState()
    {
        self::$preloaded_counts = [];
    }

    public static function countByProject(Project $project, bool $use_cache = true): int
    {
        if ($use_cache
            && self::$preloaded_counts !== false
            && array_key_exists($project->getId(), self::$preloaded_counts)
        ) {
            return isset(self::$preloaded_counts[$project->getId()]) ? self::$preloaded_counts[$project->getId()] : 0;
        } else {
            return static::count(
                [
                    '`project_id` = ? AND `completed_on` IS NULL AND `is_trashed` = ?',
                    $project->getId(),
                    false,
                    false,
                ]
            );
        }
    }

    /**
     * @param  Project|int $project
     * @return int
     */
    public static function getNextPositionInProject($project)
    {
        return DB::executeFirstCell(
            'SELECT MAX(`position`) FROM `task_lists` WHERE `project_id` = ?',
            ($project instanceof Project ? $project->getId() : $project)
        ) + 1;
    }

    /**
     * Reorder task lists.
     *
     * @param TaskList[]|int[] $task_lists
     */
    public static function reorder($task_lists, Project $project, User $by)
    {
        $task_list_ids = [];

        if ($task_lists && is_foreachable($task_lists)) {
            DB::transact(function () use ($task_lists, $by, &$task_list_ids) {
                $counter = 1;

                $user_id = DB::escape($by->getId());
                $user_name = DB::escape($by->getDisplayName());
                $user_email = DB::escape($by->getEmail());

                foreach ($task_lists as $task_list) {
                    $task_list_id = $task_list instanceof TaskList ? $task_list->getId() : $task_list;

                    DB::execute("UPDATE task_lists SET position = ?, updated_on = UTC_TIMESTAMP(), updated_by_id = $user_id, updated_by_name = $user_name, updated_by_email = $user_email WHERE id = ?", $counter++, $task_list_id);

                    $task_list_ids[] = $task_list_id;
                }
            }, 'Reordering task lists');
        }

        if (count($task_list_ids)) {
            DB::execute('UPDATE tasks SET updated_on = UTC_TIMESTAMP() WHERE task_list_id IN (?)', $task_list_ids); // Force My Tasks and My Team Teasks collections to refresh
            DataObjectPool::announce(
                new TaskListReorderedEvent(
                    TaskLists::findById($task_list_ids[0])
                )
            );
        }

        AngieApplication::cache()->removeByObject($project, 'first_task_list_id');

        self::clearCacheFor($task_list_ids);
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------

    /**
     * Find successive task lists by a given task list.
     *
     * @return array
     */
    public static function findSuccessiveByTaskLists(TaskList $task_list)
    {
        $start_on = $task_list->getStartOn();

        if ($start_on instanceof DateValue) {
            return self::find(
                [
                    'conditions' => [
                        'project_id = ? AND start_on > ? AND is_trashed = ? AND id != ?',
                        $task_list->getProjectId(),
                        $start_on,
                        false,
                        $task_list->getId(),
                    ],
                    'order' => 'start_on',
                ]
            );
        }

        return null;
    }

    // ---------------------------------------------------
    //  Utilities
    // ---------------------------------------------------

    /**
     * Returns ID name map.
     *
     * $filter can be:
     *
     * - Project instance, only task lists from that project will be returned
     * - Array of task list IDs
     * - NULL, in that case all task lists with given state will be returned
     *
     * @param  Project|array|null $filter
     * @return array
     */
    public static function getIdNameMap($filter = null)
    {
        if ($filter instanceof Project) {
            $rows = DB::execute(
                'SELECT `id`, `name` FROM task_lists WHERE project_id = ? AND is_trashed = ? ORDER BY ' . self::getDefaultOrderBy(),
                $filter->getId(),
                false
            );
        } elseif (is_array($filter)) {
            $rows = DB::execute(
                'SELECT `id`, `name` FROM task_lists WHERE id IN (?) AND is_trashed = ? ORDER BY ' . self::getDefaultOrderBy(),
                $filter,
                false
            );
        } else {
            $rows = DB::execute(
                'SELECT `id`, `name` FROM task_lists WHERE is_trashed = ? ORDER BY ' . self::getDefaultOrderBy(),
                false
            );
        }

        $result = [];

        if ($rows) {
            foreach ($rows as $row) {
                $result[(int) $row['id']] = $row['name'];
            }
        }

        return empty($result) ? null : $result;
    }

    /**
     * Return ID-s by list of task list names.
     *
     * @param  array   $names
     * @param  Project $project
     * @return array
     */
    public static function getIdsByNames($names, $project = null)
    {
        if ($names) {
            if ($project instanceof Project) {
                $ids = DB::executeFirstColumn('SELECT id FROM task_lists WHERE project_id = ? AND name IN (?)', $project->getId(), $names);
            } else {
                $ids = DB::executeFirstColumn('SELECT id FROM task_lists WHERE name IN (?)', $names);
            }

            if ($ids) {
                foreach ($ids as $k => $v) {
                    $ids[$k] = (int) $v;
                }
            }

            return $ids;
        }

        return null;
    }

    /**
     * Return date when first project task list starts on.
     *
     * @return DateValue
     */
    public static function getFirstTaskListStartsOn(Project $project)
    {
        if ($first_task_list_starts_on = DB::executeFirstCell('SELECT start_on FROM task_lists WHERE project_id = ? AND is_trashed = ? AND start_on IS NOT NULL ORDER BY start_on', $project->getId(), false)) {
            return DateValue::makeFromString($first_task_list_starts_on);
        }

        return DateValue::make(
            $project->getCreatedOn()->getMonth(),
            $project->getCreatedOn()->getDay(),
            $project->getCreatedOn()->getYear()
        );
    }

    public static function getFirstTaskList(Project $project, bool $use_cache = true): ?TaskList
    {
        return DataObjectPool::get(
            TaskList::class,
            self::getFirstTaskListId($project, $use_cache)
        );
    }

    public static function getFirstTaskListId(Project $project, bool $use_cache = true): int
    {
        return (int) AngieApplication::cache()->getByObject(
            $project,
            'first_task_list_id',
            function () use ($project) {
                $task_list_id = DB::executeFirstCell(
                    'SELECT `id` FROM `task_lists` WHERE `project_id` = ? AND `is_trashed` = ? AND `completed_on` IS NULL ORDER BY `position`',
                    $project->getId(),
                    false
                );

                if ($task_list_id) {
                    return $task_list_id;
                }

                return self::create(
                    [
                        'name' => ConfigOptions::getValue('default_task_list_name'),
                        'project_id' => $project->getId(),
                    ]
                )->getId();
            },
            !$use_cache
        );
    }

    public static function whatIsWorthRemembering(): array
    {
        return [
            'project_id',
            'name',
            'completed_on',
            'is_trashed',
        ];
    }

    public static function findNextPositionInProject(Project $project)
    {
        return DB::executeFirstCell(
            'SELECT MAX(position) FROM task_lists WHERE project_id = ?',
            $project->getId()
        ) + 1;
    }

    public static function insert(
        User $by,
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): array
    {
        $task_list = null;

        DB::transact(function () use ($by, $attributes, $save, &$task_list) {
            DB::execute('
            UPDATE task_lists
            SET position = position+1, updated_on = UTC_TIMESTAMP(), updated_by_id = ?, updated_by_name = ?, updated_by_email = ?
            WHERE project_id = ? AND position >= ? AND is_trashed = ? AND completed_on IS NULL', $by->getId(), $by->getName(), $by->getEmail(), $attributes['project_id'], $attributes['position'], false);

            $task_list = parent::create($attributes, $save, false);
        });

        $task_lists = DB::executeFirstColumn('
        SELECT id
        FROM task_lists
        WHERE project_id = ? AND is_trashed = ? AND completed_on IS NULL
        ORDER BY position ASC, id DESC
        ', $attributes['project_id'], false);

        if ($announce) {
            DataObjectPool::announce(new TaskListInsertedEvent($task_list));
            call_user_func(
              AngieApplication::getContainer()->get(WebhookDispatcherInterface::class),
              new TaskListCreatedEvent($task_list)
            );
        }

        return [
            'single' => $task_list,
            'order' => $task_lists,
        ];
    }
}
