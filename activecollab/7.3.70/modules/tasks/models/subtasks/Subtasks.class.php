<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents\SubtaskCompletedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents\SubtaskCreatedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents\SubtaskReopenedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents\SubtaskReorderedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents\SubtaskUpdatedEvent;
use ActiveCollab\Module\Tasks\Events\Subtask\SubtaskPromotedToTaskEvent;
use Angie\Globalization;

/**
 * Subtasks class.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage models
 */
class Subtasks extends BaseSubtasks
{
    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'subtasks_for_task')) {
            $task_id = (int) array_pop($bits);

            $task = DataObjectPool::get('Task', $task_id);

            if ($task instanceof Task) {
                $collection->setConditions('task_id = ?', $task->getId());
            } else {
                throw new ImpossibleCollectionError("Task #{$task_id} not found");
            }
        } elseif (str_starts_with($collection_name, 'open_subtasks_assigned_to_user')) {
            $assignee_id = array_pop($bits);

            $assignee = DataObjectPool::get('User', $assignee_id);

            if ($assignee instanceof User) {
                $project_ids = Users::prepareProjectIdsFilterByUser($user);

                if ($project_ids === true) {
                    $collection->setConditions('assignee_id = ? AND completed_on IS NULL AND is_trashed = ?', $assignee->getId(), false);
                } else {
                    $collection->setJoinTable('tasks', ['task_id', 'id']);
                    $collection->setConditions('tasks.project_id IN (?) AND subtasks.assignee_id = ? AND subtasks.completed_on IS NULL AND subtasks.is_trashed = ?', $project_ids, $assignee->getId(), false);
                    $collection->setOrderBy('ISNULL(tasks.position) ASC, tasks.position, tasks.created_on');
                }
            } else {
                throw new ImpossibleCollectionError("Assignee #{$assignee_id} not found");
            }
        } elseif (str_starts_with($collection_name, 'open_subtasks_assigned_to_team')) {
            $team_id = array_pop($bits);

            $team = DataObjectPool::get('Team', $team_id);

            if ($team instanceof Team && $team->countMembers()) {
                $project_ids = Users::prepareProjectIdsFilterByUser($user);

                if ($project_ids === true) {
                    $collection->setConditions('assignee_id IN (?) AND completed_on IS NULL AND is_trashed = ?', $team->getMemberIds(), false);
                } else {
                    $collection->setJoinTable('tasks', ['task_id', 'id']);
                    $collection->setConditions('tasks.project_id IN (?) AND subtasks.assignee_id IN (?) AND subtasks.completed_on IS NULL AND subtasks.is_trashed = ?', $project_ids, $team->getMemberIds(), false);
                }
            } else {
                throw new ImpossibleCollectionError("Team #{$team_id} not found or team has no members");
            }
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events')) {
            self::prepareCalendarEventsCollection($collection, $collection_name, $user);
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    /**
     * Prepare calendar events collection.
     *
     * @param                  $collection_name
     * @param  User|null       $user
     * @return ModelCollection
     */
    protected static function prepareCalendarEventsCollection(ModelCollection &$collection, $collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $to = array_pop($bits);
        $from = array_pop($bits);

        $collection->setJoinTable('tasks', 'task_number');
        $collection->setOrderBy('ISNULL(tasks.position) ASC, tasks.position, tasks.created_on');

        $parts = [];

        $parts[] = DB::prepare('subtasks.due_on IS NOT NULL AND (subtasks.due_on BETWEEN ? AND ?)', $from, $to);
        $parts[] = DB::prepare('tasks.is_trashed = ? AND subtasks.is_trashed = ?', false, false);

        if ($user instanceof Client) {
            $parts[] = ' AND ' . DB::prepare('tasks.is_hidden_from_clients = ?', false);
        }

        $additional_conditions = implode(' AND ', $parts);

        // everything in all projects
        if (str_starts_with($collection_name, 'assignments_as_calendar_events_everything_in_all_projects')) {
            if ($user->isPowerUser()) {
                $collection->setConditions($additional_conditions);
            } else {
                throw new ImpossibleCollectionError('Only project managers can see everything in all projects');
            }

            // everything in my projects
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events_everything_in_my_projects')) {
            if ($user->isPowerUser() || $user->isMember() || $user->isSubcontractor()) {
                $project_ids = Projects::findIdsByUser($user, false, DB::prepare('is_trashed = ?', false));

                if ($project_ids && is_foreachable($project_ids)) {
                    $collection->setConditions("tasks.project_id IN (?) AND $additional_conditions", $project_ids);
                } else {
                    throw new ImpossibleCollectionError('User not involved in any of the projects');
                }
            } else {
                throw new ImpossibleCollectionError('User need to be Member or Subcontractor');
            }

            // only my assignments
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events_only_my_assignments')) {
            if ($user->isPowerUser() || $user->isMember() || $user->isSubcontractor()) {
                $project_ids = Projects::findIdsByUser($user, false, DB::prepare('is_trashed = ?', false));

                if ($project_ids && is_foreachable($project_ids)) {
                    $collection->setConditions("tasks.project_id IN (?) AND subtasks.assignee_id = ? AND $additional_conditions", $project_ids, $user->getId());
                } else {
                    throw new ImpossibleCollectionError('User not involved in any of the projects');
                }
            } else {
                throw new ImpossibleCollectionError('User need to be Member or Subcontractor');
            }

            // assignments for specified user
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events')) {
            $for_id = array_pop($bits);

            if ($user->isPowerUser()) {
                $for = DataObjectPool::get('User', $for_id);

                if ($for instanceof User) {
                    $project_ids = Projects::findIdsByUser($for, false, DB::prepare('is_trashed = ?', false));

                    if ($project_ids && is_foreachable($project_ids)) {
                        $collection->setConditions("tasks.project_id IN (?) AND subtasks.assignee_id = ? AND $additional_conditions", $project_ids, $for->getId());
                    } else {
                        throw new ImpossibleCollectionError('User not involved in any of the projects');
                    }
                } else {
                    throw new ImpossibleCollectionError("User #{$for_id} not found");
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

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Subtask
    {
        $subtask = parent::create($attributes, $save, $announce);
        $notify_assignee = array_var($attributes, 'notify_assignee', true, true);

        if ($subtask instanceof Subtask &&
            $subtask->isLoaded() &&
            $subtask->getAssigneeId() &&
            $subtask->getAssigneeId() != $subtask->getCreatedById() &&
            $notify_assignee
        ) {
            AngieApplication::notifications()
                ->notifyAbout('tasks/new_subtask', $subtask->getTask(), $subtask->getCreatedBy())
                ->setSubtask($subtask)
                ->sendToUsers($subtask->getAssignee());
        }

        if ($subtask->isLoaded() && $announce) {
            DataObjectPool::announce(new SubtaskCreatedEvent($subtask));
        }

        return $subtask;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Subtask
    {
        $assignee_id = $instance->getAssigneeId();

        // If assignee is changed set subtask fake assignee and email to null. Used for sample project!
        $subtask_assignee_changed = array_key_exists('assignee_id', $attributes)
            && ($assignee_id != $attributes['assignee_id'] || $attributes['assignee_id'] === null);

        if ($subtask_assignee_changed) {
            $attributes['fake_assignee_name'] = null;
            $attributes['fake_assignee_email'] = null;
        }

        $subtask = parent::update($instance, $attributes, $save);

        if ($subtask instanceof Subtask) {
            $assignee = $subtask->getAssignee();
            $notify_assignee = array_var($attributes, 'notify_assignee', true, true);

            if ($assignee instanceof User &&
                $assignee->getId() != $assignee_id &&
                $assignee->isActive() &&
                $notify_assignee
            ) {
                AngieApplication::notifications()
                    ->notifyAbout('tasks/subtask_reassigned', $subtask->getTask(), AngieApplication::authentication()->getLoggedUser())
                    ->setSubtask($subtask)
                    ->sendToUsers($assignee);
            }
        }

        self::announceSubtaskUpdate($subtask, $attributes);

        return $subtask;
    }

    private static function announceSubtaskUpdate(Subtask $subtask, array $attributes): void
    {
        if (self::isSubtaskCompletedStateChanged($attributes)) {
            if ($subtask->isCompleted()) {
                DataObjectPool::announce(new SubtaskCompletedEvent($subtask));
            } else {
                DataObjectPool::announce(new SubtaskReopenedEvent($subtask));
            }
        }

        DataObjectPool::announce(new SubtaskUpdatedEvent($subtask));
    }

    private static function isSubtaskCompletedStateChanged(array $attributes): bool
    {
        foreach (['completed_by_id', 'completed_by_name', 'completed_by_email', 'completed_on'] as $attribute) {
            if (array_key_exists($attribute, $attributes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reorder subtasks.
     * Reverse, if true put source subtask after target subtask and vice versa.
     *
     * @param  bool  $before
     * @return array
     */
    public static function reorder(Subtask $source, Subtask $target, $before = false)
    {
        $ordered_subtasks = [];
        $affected_subtasks = [$source->getId()];
        $target_task = $target->getTask();

        DB::transact(function () use ($source, $target, $target_task, $before, &$affected_subtasks, &$ordered_subtasks) {
            $conditions = [
                DB::prepare('id != ?', $source->getId()),
                DB::prepare('task_id = ?', $target_task->getId()),
            ];
            $conditions = implode(' AND ', $conditions);
            $query = "SELECT id FROM subtasks WHERE $conditions ORDER BY position ASC";

            $position = 1;
            $when_then_cases = '';

            if ($subtasks_after_position = DB::executeFirstColumn($query)) {
                $position = count($subtasks_after_position) + 1;
                $position_counter = 1;
                $shift_next_positon = false;

                foreach ($subtasks_after_position as $subtask_after_position_id) {
                    if ($shift_next_positon) {
                        $position = $position_counter;
                        ++$position_counter;
                        $shift_next_positon = false;
                    }

                    if ($subtask_after_position_id == $target->getId()) {
                        if ($before) {
                            $position = $position_counter;
                            ++$position_counter;
                        } else {
                            $shift_next_positon = true;
                        }
                    }

                    $when_then_cases .= "WHEN {$subtask_after_position_id} THEN {$position_counter} ";

                    $affected_subtasks[] = $subtask_after_position_id;
                    $ordered_subtasks[$position_counter] = $subtask_after_position_id;

                    ++$position_counter;
                }
            }

            $when_then_cases .= "WHEN {$source->getId()} THEN $position ";
            $ordered_subtasks[$position] = $source->getId();

            DB::execute(
                "UPDATE subtasks SET updated_on = UTC_TIMESTAMP(), position = (CASE id $when_then_cases END) WHERE id IN (?)",
                $affected_subtasks
            );
        });

        self::clearCacheFor($affected_subtasks);

        $target_task->touch();

        DataObjectPool::announce(new SubtaskReorderedEvent($source));

        ksort($ordered_subtasks);

        return array_values($ordered_subtasks);
    }

    /**
     * Advance subtasks by parent.
     *
     * @param Task $parent
     * @param int  $advance
     */
    public static function advanceByParent($parent, $advance)
    {
        $advance = (int) $advance;

        if ($parent instanceof ISubtasks) {
            $parent_class = get_class($parent);
            $parent_id = $parent->getId();
        } elseif (is_array($parent) && count($parent) == 2) {
            [$parent_class, $parent_id] = $parent;
        } else {
            throw new InvalidParamError('parent', $parent, '$parent is expected to be ISubtasks instance or an array where first element is parent type and second is parent id');
        }

        if ($advance != 0) {
            try {
                DB::beginWork('Rescheduling subtasks @ ' . __CLASS__);

                $subtasks = DB::execute('SELECT id, due_on FROM subtasks WHERE parent_type = ? AND parent_id = ? AND completed_on IS NULL AND due_on IS NOT NULL', $parent_class, $parent_id);

                if ($subtasks) {
                    foreach ($subtasks as $subtask) {
                        $due_on = new DateValue($subtask['due_on']);
                        $due_on->advance($advance); // Initial advance

                        while (!Globalization::isWorkday($due_on)) {
                            $due_on->advance(86400);
                        }

                        DB::execute('UPDATE subtasks SET due_on = ? WHERE id = ?', $due_on, $subtask['id']);
                    }
                }

                DB::commit('Subtasks rescheduled @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to reschedule subtasks @ ' . __CLASS__);
                throw $e;
            }

            self::clearCache();
        }
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------

    /**
     * Return all tasks that belong to a given object.
     *
     * @param  bool      $include_trashed
     * @return Subtask[]
     */
    public static function findByTask(Task $task, $include_trashed = false)
    {
        if ($include_trashed || $task->getIsTrashed()) {
            return self::find([
                'conditions' => ['task_id = ?', $task->getId()],
            ]);
        } else {
            return self::find([
                'conditions' => ['task_id = ? AND is_trashed = ?', $task->getId(), false],
            ]);
        }
    }

    public static function preloadDetailsByIds(array $subtask_ids)
    {
        DataObjectPool::getByIds(Subtask::class, $subtask_ids);
    }

    /**
     * @var array
     */
    private static $total_counts_by_task = [];
    private static $open_counts_by_task = [];

    /**
     * Preload counts for the given list of tasks.
     */
    public static function preloadCountByTasks(array $task_ids)
    {
        if ($rows = DB::execute('SELECT task_id, COUNT(id) AS "row_count" FROM subtasks WHERE task_id IN (?) AND is_trashed = ? GROUP BY task_id', $task_ids, false)) {
            foreach ($rows as $row) {
                self::$total_counts_by_task[$row['task_id']] = $row['row_count'];
            }
        }

        if (count(self::$total_counts_by_task)) {
            if ($rows = DB::execute('SELECT task_id, COUNT(id) AS "row_count" FROM subtasks WHERE task_id IN (?) AND completed_on IS NULL AND is_trashed = ? GROUP BY task_id', $task_ids, false)) {
                foreach ($rows as $row) {
                    self::$open_counts_by_task[$row['task_id']] = $row['row_count'];
                }
            }
        }

        if ($zeros = array_diff($task_ids, array_keys(self::$total_counts_by_task))) {
            foreach ($zeros as $task_with_zero_subtasks) {
                self::$total_counts_by_task[$task_with_zero_subtasks] = 0;
            }
        }

        if ($zeros = array_diff($task_ids, array_keys(self::$open_counts_by_task))) {
            foreach ($zeros as $task_with_zero_open_subtasks) {
                self::$open_counts_by_task[$task_with_zero_open_subtasks] = 0;
            }
        }
    }

    /**
     * Reset manager state (between tests for example).
     */
    public static function resetState()
    {
        self::$total_counts_by_task = [];
        self::$open_counts_by_task = [];
    }

    public static function countByTaskId(int $task_id, bool $is_trashed): array
    {
        if (isset(self::$total_counts_by_task[$task_id]) && isset(self::$open_counts_by_task[$task_id])) {
            return [self::$total_counts_by_task[$task_id], self::$open_counts_by_task[$task_id]];
        } else {
            if ($is_trashed) {
                return [
                    DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM subtasks WHERE task_id = ?', $task_id),
                    DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM subtasks WHERE task_id = ? AND completed_on IS NULL', $task_id),
                ];
            } else {
                return [
                    DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM subtasks WHERE task_id = ? AND is_trashed = ?', $task_id, false),
                    DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM subtasks WHERE task_id = ? AND is_trashed = ? AND completed_on IS NULL', $task_id, false),
                ];
            }
        }
    }

    public static function countByTask(Task $task, $use_cache = true)
    {
        return self::countByTaskId($task->getId(), $task->getIsTrashed());
    }

    /**
     * Return open tasks that belong to a given object.
     *
     * @return Subtask[]
     */
    public static function findOpenByTask(Task $task)
    {
        return self::find([
            'conditions' => ['task_id = ? AND is_trashed = ? AND completed_on IS NULL', $task->getId(), false],
        ]);
    }

    /**
     * Return only completed tasks that belong to a specific object.
     *
     * @return Subtask[]
     */
    public static function findCompletedByTask(Task $task)
    {
        return self::find([
            'conditions' => ['task_id = ? AND state >= ? AND completed_on IS NOT NULL', $task->getId()],
            'order' => 'completed_on DESC',
        ]);
    }

    /**
     * Return next position by parent object.
     *
     * @return int
     */
    public static function nextPositionByTask(Task $task)
    {
        return (int) DB::executeFirstCell('SELECT MAX(position) FROM subtasks WHERE task_id = ?', $task->getId()) + 1;
    }

    // ---------------------------------------------------
    //  State
    // ---------------------------------------------------

    /**
     * Trash subtasks attached to a given parent object.
     */
    public static function deleteByTask(Task $task)
    {
        if ($subtask_ids = DB::executeFirstColumn('SELECT id FROM subtasks WHERE task_id = ?', $task->getId())) {
            try {
                DB::beginWork('Dropping subtasks @ ' . __CLASS__);

                DB::execute('DELETE FROM subtasks WHERE id IN (?)', $subtask_ids);

                Subscriptions::deleteByParents(['Subtask' => $subtask_ids]);

                DB::commit('Subtasks dropped @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to drop subtasks @ ' . __CLASS__);
                throw $e;
            }
        }
    }

    /**
     * Delete entries by parents.
     *
     * $parents is an array where key is parent type and value is array of
     * object ID-s of that particular parent
     *
     * @param array $parents
     */
    public static function deleteByParents($parents)
    {
        try {
            DB::beginWork('Removing subtasks by parent type and parent IDs @ ' . __CLASS__);

            if (is_foreachable($parents)) {
                foreach ($parents as $parent_type => $parent_ids) {
                    $rows = DB::execute('SELECT id, type FROM subtasks WHERE parent_type = ? AND parent_id IN (?)', $parent_type, $parent_ids);

                    if ($rows) {
                        $subtasks = [];

                        foreach ($rows as $row) {
                            if (array_key_exists($row['type'], $subtasks)) {
                                $subtasks[$row['type']][] = (int) $row['id'];
                            } else {
                                $subtasks[$row['type']] = [(int) $row['id']];
                            }
                        }

                        DB::execute('DELETE FROM subtasks WHERE parent_type = ? AND parent_id IN (?)', $parent_type, $parent_ids);

                        ActivityLogs::deleteByParents($subtasks);
                        Subscriptions::deleteByParents($subtasks);
                        ModificationLogs::deleteByParents($subtasks);
                    }
                }
            }

            DB::commit('Comments removed by parent type and parent IDs @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to remove comments by parent type and parent IDs @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * @return Task
     */
    public static function promoteToTask(Subtask $subtask, Task $parent_task, User $by)
    {
        if ($subtask->isCompleted()) {
            throw new InvalidParamError('subtask', $subtask, 'Completed subtasks cannot be promoted to tasks');
        }

        $task = null;

        DB::transact(
            function () use ($subtask, $by, $parent_task, &$task) {
                $project = $subtask->getTask()->getProject();

                $task_list = $subtask->getTask()->getTaskList();
                if (empty($task_list) || $task_list->isCompleted() || $task_list->getIsTrashed()) {
                    $task_list = TaskLists::getFirstTaskList($project);
                }

                $body = '';
                $name = $subtask->getBody();

                if (mb_strlen($name) > 150) {
                    $name = strtok(wordwrap($subtask->getBody(), 149, "\n"), "\n") . '…';
                    $body = $subtask->getBody();
                }

                $task = Tasks::create(
                    [
                        'project_id' => $project->getId(),
                        'task_list_id' => $task_list->getId(),
                        'name' => $name,
                        'body' => $body,
                        'assignee_id' => $subtask->getAssigneeId(),
                        'created_by_id' => $by->getId(),
                    ]
                );

                $task->subscribe($by);

                $subtask->moveToTrash($by);

                AngieApplication::eventsDispatcher()->trigger(new SubtaskPromotedToTaskEvent($parent_task, $task, $by));
            }
        );

        return $task;
    }

    // ---------------------------------------------------
    //  Activity logs
    // ---------------------------------------------------

    public static function whatIsWorthRemembering(): array
    {
        return [
            'assignee_id',
            'completed_on',
            'is_trashed',
        ];
    }

    /**
     * Rebuild update activities.
     */
    public static function rebuildUpdateActivites()
    {
        if ($modifications = DB::execute('SELECT DISTINCT l.id, l.parent_id, l.created_on, l.created_by_id, l.created_by_name, l.created_by_email FROM modification_logs AS l LEFT JOIN modification_log_values AS lv ON l.id = lv.modification_id WHERE l.parent_type = ? AND lv.field IN (?)', 'Subtask', self::whatIsWorthRemembering())) {
            $subtask_ids = [];
            $modification_ids = [];

            foreach ($modifications as $modification) {
                $modification_ids[] = $modification['id'];

                if (!in_array($modification['parent_id'], $subtask_ids)) {
                    $subtask_ids[] = $modification['parent_id'];
                }
            }

            $subtask_task_map = [];
            $task_ids = [];

            foreach (DB::executeFirstColumn('SELECT id, task_id FROM subtasks WHERE id IN (?)', $subtask_ids) as $row) {
                $subtask_task_map[$row['id']] = $row['task_id'];

                if (!in_array($row['task_id'], $task_ids)) {
                    $task_ids[] = $row['task_id'];
                }
            }

            $subtask_modifications = ActivityLogs::prepareFieldValuesForSerialization($modification_ids, self::whatIsWorthRemembering());
            $task_paths = Tasks::getParentPathsByElementIds($task_ids);

            $batch = new DBBatchInsert('activity_logs', ['type', 'parent_type', 'parent_id', 'parent_path', 'created_on', 'created_by_id', 'created_by_name', 'created_by_email', 'raw_additional_properties']);

            foreach ($modifications as $modification) {
                $subtask_id = $modification['parent_id'];

                if (isset($subtask_task_map[$subtask_id])) {
                    $task_id = $subtask_task_map[$subtask_id];

                    $batch->insertArray([
                        'type' => 'SubtaskUpdatedActivityLog',
                        'parent_type' => 'Task',
                        'parent_id' => $task_id,
                        'parent_path' => isset($task_paths[$task_id]) ? $task_paths[$task_id] : '',
                        'created_on' => $modification['created_on'],
                        'created_by_id' => $modification['created_by_id'],
                        'created_by_name' => $modification['created_by_name'],
                        'created_by_email' => $modification['created_by_email'],
                        'raw_additional_properties' => serialize(['subtask_id' => $subtask_id, 'modifications' => $subtask_modifications[$subtask_id]]),
                    ]);
                }
            }

            $batch->done();
        }
    }

    /**
     * Revoke assignee on all subtasks where $user is assigned.
     */
    public static function revokeAssignee(User $user, User $by)
    {
        if (!$user->canChangeRole($by, false)) {
            throw new InsufficientPermissionsError();
        }

        if ($subtasks_assigned_to = self::findBy('assignee_id', $user->getId())) {
            foreach ($subtasks_assigned_to as $subtask) {
                $subtask->setAssignee(null, $by);
            }
        }
    }
}
