<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class OpenAssignmentsForAssigneeCollection extends AssignmentsCollection
{
    private ?User $assignee = null;
    private ?ModelCollection $tasks_collection = null;
    private ?ModelCollection $subtasks_collection = null;

    public function setAssignee(User $assignee)
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function getContextTimestamp(): string
    {
        return $this->assignee->getUpdatedOn()->toMySQL();
    }

    public function getModelName(): string
    {
        return 'Users';
    }

    protected function getTasksCollections(): ModelCollection
    {
        if (empty($this->tasks_collection)) {
            if (!$this->assignee instanceof User || !$this->getWhosAsking() instanceof User) {
                throw new ImpossibleCollectionError('Invalid user and/or who is asking instance');
            }

            $task_ids = $this->getTaskIds();

            if (!empty($task_ids)) {
                Comments::preloadCountByParents(Task::class, $task_ids);
                Subtasks::preloadCountByTasks($task_ids);
                Attachments::preloadDetailsByParents(Task::class, $task_ids);
                Labels::preloadDetailsByParents(Task::class, $task_ids);
                TaskDependencies::preloadCountByTasks($task_ids);
            }

            $this->tasks_collection = Tasks::prepareCollection(
                'open_user_tasks_for_my_work',
                $this->getWhosAsking()
            );
            $this->tasks_collection->setOrderBy('created_on DESC, position ASC');

            if (empty($task_ids)) {
                $this->tasks_collection->setConditions('id = ?', 0); // Better than ImpossibleCollectionError.
            } else {
                try {
                    $project_ids = Users::prepareProjectIdsFilterByUser($this->getWhosAsking());

                    if ($project_ids === true) {
                        $this->tasks_collection->setConditions('id IN (?)', $task_ids);
                    } else {
                        $this->tasks_collection->setConditions(
                            '`id` IN (?) AND project_id IN (?)',
                            $task_ids,
                            $project_ids,
                        );
                    }
                } catch (ImpossibleCollectionError $e) {
                    $this->tasks_collection->setConditions('id = ?', 0); // Better than ImpossibleCollectionError.
                }
            }
        }

        return $this->tasks_collection;
    }

    protected function getSubtasksCollection(): ModelCollection
    {
        if (empty($this->subtasks_collection)) {
            if (!$this->assignee instanceof User || !$this->getWhosAsking() instanceof User) {
                throw new ImpossibleCollectionError('Invalid user and/or who is asking instance');
            }

            $this->subtasks_collection = Subtasks::prepareCollection(
                'open_subtasks_assigned_to_user_' . $this->assignee->getId(),
                $this->getWhosAsking()
            );
            $this->subtasks_collection->setOrderBy('created_on DESC, position ASC');
            $this->subtasks_collection->setPagination(1, $this->getMaxNumberOfTasks());
        }

        return $this->subtasks_collection;
    }

    private ?array $task_ids = null;

    private function getTaskIds(): array
    {
        if ($this->task_ids === null) {
            $this->task_ids = DB::executeFirstColumn(
                sprintf('SELECT `id`
                    FROM `tasks`
                    WHERE `assignee_id` = ? AND `completed_on` IS NULL AND `is_trashed` = ?
                    ORDER BY `created_on` DESC
                    LIMIT 0, %d',
                    $this->getMaxNumberOfTasks()
                ),
                $this->assignee->getId(),
                false
            );

            if (empty($this->task_ids)) {
                $this->task_ids = [];
            }
        }

        return $this->task_ids;
    }

    private function getMaxNumberOfTasks(): int
    {
        return 500;
    }
}
