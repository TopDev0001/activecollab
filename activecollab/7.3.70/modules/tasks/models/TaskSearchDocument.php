<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @method Task getProducer()
 */
final class TaskSearchDocument extends ProjectElementSearchDocument
{
    public function __construct(Task $task)
    {
        parent::__construct($task);
    }

    protected function getTimestamps()
    {
        return array_unique(
            array_merge(
                parent::getTimestamps(),
                $this->querySubtasks()[0]
            )
        );
    }

    protected function getCreatedById()
    {
        return array_unique(
            array_merge(
                parent::getCreatedById(),
                $this->querySubtasks()[1]
            )
        );
    }

    protected function getAssigneeId()
    {
        $result = parent::getAssigneeId();

        $task_assignee_id = $this->getProducer()->getAssigneeId();

        if (!empty($task_assignee_id) && !in_array($task_assignee_id, $result)) {
            $result[] = $task_assignee_id;
        }

        return array_unique(
            array_merge(
                $result,
                $this->querySubtasks()[2]
            )
        );
    }

    protected function getLabelId()
    {
        return array_unique(
            array_merge(
                parent::getLabelId(),
                $this->getProducer()->getLabelIds()
            )
        );
    }

    protected function getBodyExtensions()
    {
        return array_merge(
            parent::getBodyExtensions(),
            $this->querySubtasks()[3]
        );
    }

    private $queried_subtasks = null;

    private function querySubtasks()
    {
        if ($this->queried_subtasks === null) {
            $timestamps = [];
            $created_by_id = [];
            $assignee_id = [];
            $bodies = [];

            if ($subtasks = DB::execute(
                'SELECT `body`, `created_on`, `created_by_id`, `assignee_id`
                    FROM `subtasks`
                    WHERE `task_id` = ? AND `is_trashed` = ? ORDER BY `created_on`',
                $this->getProducer()->getId(),
                false
            )) {
                foreach ($subtasks as $subtask) {
                    $bodies[] = $subtask['body'];

                    if (!in_array($subtask['created_on'], $timestamps)) {
                        $timestamps[] = $subtask['created_on'];
                    }

                    if (!empty($subtask['created_by_id']) && !in_array($subtask['created_by_id'], $created_by_id)) {
                        $created_by_id[] = $subtask['created_by_id'];
                    }

                    if (!empty($subtask['assignee_id']) && !in_array($subtask['assignee_id'], $assignee_id)) {
                        $assignee_id[] = $subtask['assignee_id'];
                    }
                }
            }

            $this->queried_subtasks = [
                $timestamps,
                $created_by_id,
                $assignee_id,
                $bodies,
            ];
        }

        return $this->queried_subtasks;
    }
}
