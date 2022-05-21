<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tasks\Utils\GhostTasks\GhostTaskInterface;
use ActiveCollab\Module\Tasks\Utils\GhostTasks\Resolver\GhostTasksResolverInterface;
use ActiveCollab\Module\Tasks\Utils\RecurringTasksTrigger\RecurringTasksTriggerInterface;

class RecurringTasks extends BaseRecurringTasks
{
    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'project_recurring_tasks')) {
            return self::prepareRecurringTasksCollectionByProject($collection_name, $user);
        } else {
            if (str_starts_with($collection_name, 'all_recurring_tasks_in_project')) {
                self::prepareAssignmentsCollectionByProject($collection, $collection_name, $user);
            } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events')) {
                self::prepareCalendarEventsCollection($collection, $collection_name, $user);
            } else {
                throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
            }
        }

        return $collection;
    }

    /**
     * Prepare recurring tasks collection filtered by project ID.
     *
     * @param  string                          $collection_name
     * @return ProjectRecurringTasksCollection
     */
    private static function prepareRecurringTasksCollectionByProject($collection_name, User $user)
    {
        $bits = explode('_', $collection_name);

        /** @var Project $project */
        if ($project = DataObjectPool::get('Project', array_pop($bits))) {
            return (new ProjectRecurringTasksCollection($collection_name))->setProject($project)->setWhosAsking($user);
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Project ID expected in collection name');
        }
    }

    /**
     * Prepare recurring tasks collection filtered by project ID.
     *
     * @param string $collection_name
     * @param User   $user
     */
    private static function prepareAssignmentsCollectionByProject(ModelCollection &$collection, $collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        $project_id = array_pop($bits);

        $project = DataObjectPool::get('Project', $project_id);

        if ($project instanceof Project) {
            $collection->setOrderBy('position');

            if (str_starts_with($collection_name, 'all_recurring_tasks_in_project')) {
                if ($user instanceof Client) {
                    $collection->setConditions('project_id = ? AND is_trashed = ? AND is_hidden_from_clients = ?',
                        $project->getId(), false, false);
                } else {
                    $collection->setConditions('project_id = ? AND is_trashed = ?', $project->getId(), false, false);
                }
            } else {
                throw new InvalidParamError('collection_name', $collection_name);
            }
        } else {
            throw new ImpossibleCollectionError("Project #{$project_id} not found");
        }
    }

    /**
     * Prepare calendar events collection.
     *
     * @param  string          $collection_name
     * @param  User|null       $user
     * @return ModelCollection
     */
    private static function prepareCalendarEventsCollection(ModelCollection &$collection, $collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $parts = [
            DB::prepare('start_in IS NOT NULL AND due_in IS NOT NULL AND is_trashed = ? AND repeat_frequency <> ?',
                false, RecurringTask::REPEAT_FREQUENCY_NEVER),
        ];

        if ($user instanceof Client) {
            $parts[] = DB::prepare('is_hidden_from_clients = ?', false);
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
            $project_ids = Projects::findIdsByUser($user, false, DB::prepare('is_trashed = ?', false));

            if ($project_ids && is_foreachable($project_ids)) {
                $collection->setConditions("project_id IN (?) AND $additional_conditions", $project_ids);
            } else {
                throw new ImpossibleCollectionError('User not involved in any of the projects');
            }

            // only my assignments
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events_only_my_assignments')) {
            if ($user->isPowerUser() || $user->isMember() || $user->isSubcontractor()) {
                $project_ids = Projects::findIdsByUser($user, false, DB::prepare('is_trashed = ?', false));

                if ($project_ids && is_foreachable($project_ids)) {
                    $collection->setConditions("project_id IN (?) AND assignee_id = ? AND $additional_conditions", $project_ids, $user->getId());
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
                        $collection->setConditions("project_id IN (?) AND assignee_id = ? AND $additional_conditions", $project_ids, $for->getId());
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

    public static function whatIsWorthRemembering(): array
    {
        return [
            'name',
            'task_list_id',
            'assignee_id',
            'estimate',
            'job_type_id',
            'due_in',
            'is_important',
            'completed_on',
            'is_trashed',
        ];
    }

    /**
     * Returns true if $user can create a new recurring task in $project.
     *
     * @param  User|Client $user
     * @return bool
     */
    public static function canAdd(User $user, Project $project)
    {
        if ($project->isCompleted() || $project->getIsTrashed()) {
            return false;
        }

        if ($user->isClient() && !$user->canManageTasks()) {
            return false;
        }

        return $user instanceof User && ($user->isOwner() || $project->isMember($user));
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): RecurringTask
    {
        self::prepareAttributes($attributes);

        try {
            DB::beginWork('Begin: create new recurring task @ ' . __CLASS__);

            $recurring_task = parent::create($attributes, $save, false);

            if ($recurring_task instanceof RecurringTask) {
                if (isset($attributes['attachments'])) {
                    $ids = [0];
                    foreach ($attributes['attachments'] as $attachment) {
                        $ids[] = $attachment['id'];
                    }

                    if ($attachments = Attachments::findByIds($ids)) {
                        /** @var User $authenticated_user */
                        $authenticated_user = AngieApplication::authentication()->getAuthenticatedUser();

                        foreach ($attachments as $attachment) {
                            if ($attachment instanceof WarehouseAttachment) {
                                $recurring_task->attachWarehouseFile($attachment, $authenticated_user);
                            } elseif ($attachment instanceof GoogleDriveAttachment
                                || $attachment instanceof DropboxAttachment
                            ) {
                                $recurring_task->attachExternalFile($attachment, $authenticated_user);
                            } else {
                                $recurring_task->attachFile(
                                    $attachment->getPath(),
                                    $attachment->getName(),
                                    $attachment->getMimeType(),
                                    $authenticated_user
                                );
                            }
                        }
                    }
                }

                AngieApplication::getContainer()
                    ->get(RecurringTasksTriggerInterface::class)
                        ->processRecurringTask($recurring_task, DateTimeValue::now()->getSystemDate());
            }

            DB::commit('Recurring task created @ ' . __CLASS__);

            AngieApplication::log()->event(
                'recurring_task_created',
                'Recurring task #{recurring_task_id} has been created ({repeat_frequency})',
                [
                    'recurring_task_id' => $recurring_task->getId(),
                    'repeat_frequency' => $recurring_task->getRepeatFrequency(),
                ]
            );

            return DataObjectPool::announce(
                $recurring_task,
                DataObjectPool::OBJECT_CREATED,
                $attributes
            );
        } catch (Exception $e) {
            DB::rollback('Rollback: create new recurring task @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Prepare attributes for new recurring task.
     */
    private static function prepareAttributes(array &$attributes)
    {
        if (!array_key_exists('repeat_frequency', $attributes)) {
            $attributes['repeat_frequency'] = RecurringTask::REPEAT_FREQUENCY_NEVER;
        }

        if ((array_key_exists('job_type_id', $attributes) && empty($attributes['job_type_id'])) ||
            (array_key_exists('estimate', $attributes) && empty($attributes['estimate']))
        ) {
            $attributes['job_type_id'] = 0;
            $attributes['estimate'] = 0;
        }

        if (!array_key_exists('start_in', $attributes)) {
            $attributes['start_in'] = null;
        }
        if (!array_key_exists('due_in', $attributes)) {
            $attributes['due_in'] = null;
        }

        self::prepareRepeatAmount($attributes);

        if (!empty($attributes['is_hidden_from_clients'])) {
            $assignee = !empty($attributes['assignee_id'])
                ? DataObjectPool::get(User::class, $attributes['assignee_id'])
                : null;

            if ($assignee instanceof User && $assignee->isClient()) {
                throw new LogicException("Recurring task can not be assigned to a client if it's hidden from clients");
            }
        }

        // prepare Subtasks
        if (!empty($attributes['subtasks']) && is_array($attributes['subtasks'])) {
            foreach ($attributes['subtasks'] as $subtask_id => $subtask) {
                if (empty($subtask['body'])) {
                    unset($attributes['subtasks'][$subtask_id]);
                    continue;
                }

                if (!empty($attributes['is_hidden_from_clients'])) {
                    $assignee = !empty($subtask['assignee_id'])
                        ? DataObjectPool::get(User::class, $subtask['assignee_id'])
                        : null;

                    if ($assignee instanceof User && $assignee->isClient()) {
                        throw new LogicException("Recurring task can not be assigned to a client if it's hidden from clients");
                    }
                }
            }
        }
    }

    /**
     * Prepare repeat_amount value based on repeat_frequency.
     */
    private static function prepareRepeatAmount(array &$attributes)
    {
        if (!array_key_exists('repeat_amount', $attributes)) {
            $attributes['repeat_amount'] = null; // We have to start somewhere...
        }

        // Case for repeat_frequently 'Daily' and repeat_amount if is null to set 1
        if ($attributes['repeat_frequency'] == RecurringTask::REPEAT_FREQUENCY_DAILY
            && $attributes['repeat_amount'] === null
        ) {
            $attributes['repeat_amount'] = 1;
        }

        // Repeat amount can't be NULL, so lets set it to 0 if we still have NULL
        if (empty($attributes['repeat_amount'])) {
            $attributes['repeat_amount'] = 0;
        }
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): RecurringTask
    {
        self::prepareAttributes($attributes);

        try {
            DB::beginWork('Begin: update existing recurring task @ ' . __CLASS__);

            if (isset($attributes['subscribers'])) {
                $instance->setSubscribers($attributes['subscribers']);
            }

            $recurring_task = parent::update($instance, $attributes, $save);

            DB::commit('Recurring task created @ ' . __CLASS__);

            if ($recurring_task instanceof RecurringTask) {
                AngieApplication::getContainer()
                    ->get(RecurringTasksTriggerInterface::class)
                        ->processRecurringTask($recurring_task, DateTimeValue::now()->getSystemDate());
            }

            return $recurring_task;
        } catch (Exception $e) {
            DB::rollback('Failed to update a recurring task @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Revoke assignee on all recurring tasks and subtasks where $user is assigned.
     */
    public static function revokeAssignee(User $user, User $by)
    {
        if (!$user->canChangeRole($by, false)) {
            throw new InsufficientPermissionsError();
        }

        /** @var RecurringTask[] $recurring_tasks */
        $recurring_tasks = self::findBySQL('SELECT * FROM `recurring_tasks`');

        if ($recurring_tasks) {
            foreach ($recurring_tasks as $task) {
                $task->removeAssignee($user, $by);
            }
        }
    }

    /**
     * Return next field value in a given project id.
     *
     * @param  int    $project_id
     * @param  string $field
     * @return int
     */
    public static function findNextFieldValueByProject($project_id, $field)
    {
        return DB::executeFirstCell("SELECT MAX($field) FROM `recurring_tasks` WHERE `project_id` = ?", $project_id) + 1;
    }

    /**
     * Return recurring tasks that need to be sent on a given date.
     *
     * @return iterable|DBResult|RecurringTask[]|null
     */
    public static function getRecurringTasksToTrigger(): ?iterable
    {
        return RecurringTasks::findBySQL(
            'SELECT rt.*
                FROM recurring_tasks AS rt JOIN projects AS p ON p.id = rt.project_id
                WHERE rt.is_trashed=? AND rt.repeat_frequency != ? AND p.completed_on IS ?',
            false,
            RecurringTask::REPEAT_FREQUENCY_NEVER,
            null
        );
    }

    /**
     * @return array|GhostTaskInterface[]
     */
    public static function getRangeForCalendar(array $ids, DateValue $from_date, DateValue $to_date): array
    {
        return AngieApplication::getContainer()
            ->get(GhostTasksResolverInterface::class)
                ->getForCalendar($ids, $from_date, $to_date);
    }
}
