<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Webhooks\Transformator;

use Angie\HTML;
use Categories;
use Comment;
use DataObject;
use DB;
use Exception;
use Labels;
use Project;
use Task;
use TaskList;
use TimeRecord;
use Users;
use WebhookPayloadTransformator;

class ZapierWebhookPayloadTransformator extends WebhookPayloadTransformator
{
    public function shouldTransform(string $url): bool
    {
        return str_contains($url, 'zapier.com/hooks');
    }

    public function transform(string $event_type, DataObject $payload): ?array
    {
        if (!in_array($event_type, $this->getSupportedEvents())) {
            return null;
        }

        $transformator = sprintf('%sPayloadTransformator', $event_type);

        if (!method_exists(self::class, $transformator)) {
            throw new Exception(
                sprintf('Transformator method %s not implemented', $transformator)
            );
        }

        return $this->$transformator($payload);
    }

    public function getSupportedEvents(): array
    {
        return [
            'ProjectCreated',
            'TaskListCreated',
            'TaskCreated',
            'CommentCreated',
            'TimeRecordCreated',
            'TaskCompleted',
            'TaskListChanged',
        ];
    }

    public function ProjectCreatedPayloadTransformator(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'project_number' => $project->getProjectNumber(),
            'name' => $project->getName(),
            'label_id' => $project->getLabelId(),
            'label' => Labels::getLabelName($project->getLabelId(), '[Not-Set]'),
            'category_id' => $project->getCategoryId(),
            'category' => Categories::getCategoryName($project->getCategoryId(), '[Not-Set]'),
            'created_by_id' => $project->getCreatedById(),
            'created_by_name' => $project->getCreatedByName(),
        ];
    }

    public function TaskListCreatedPayloadTransformator(TaskList $task_list): array
    {
        return [
            'id' => $task_list->getId(),
            'name' => $task_list->getName(),
            'project_id' => $task_list->getProjectId(),
            'project_name' => $this->getProjectName($task_list->getProjectId()),
            'created_by_id' => $task_list->getCreatedById(),
            'created_by_name' => $task_list->getCreatedByName(),
        ];
    }

    public function TaskCreatedPayloadTransformator(Task $task): array
    {
        return $this->getCommonTaskProperties($task);
    }

    public function CommentCreatedPayloadTransformator(Comment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'body' => HTML::toPlainText($comment->getBody()),
            'parent_type' => $comment->getParentType(),
            'parent_id' => $comment->getParentId(),
            'parent_name' => $comment->getParent()->getName(),
            'created_by_id' => $comment->getCreatedById(),
            'created_by_name' => $comment->getCreatedByName(),
        ];
    }

    public function TimeRecordCreatedPayloadTransformator(TimeRecord $time_record): array
    {
        return [
            'id' => $time_record->getId(),
            'parent_type' => $time_record->getParentType(),
            'parent_id' => $time_record->getParentId(),
            'parent_name' => $time_record->getParent()->getName(),
            'job_type_id' => $time_record->getJobTypeId(),
            'value' => $time_record->getValue(),
            'description' => $time_record->getSummary(),
            'record_date' => $time_record->getRecordDate(),
            'record_user_id' => $time_record->getUserId(),
            'record_user_name' => $this->getUserDisplayName(
                $time_record->getUserId(),
                $time_record->getUserName(),
                $time_record->getUserEmail()
            ),
            'billable_status' => $time_record->getBillableStatus(),
            'created_by_id' => $time_record->getCreatedById(),
            'created_by_name' => $time_record->getCreatedByName(),
        ];
    }

    public function TaskCompletedPayloadTransformator(Task $task): array
    {
        return array_merge(
            $this->getCommonTaskProperties($task),
            [
                'completed_by_id' => $task->getCompletedBy()->getId(),
                'completed_by_name' => $this->getUserDisplayName(
                    $task->getCompletedById(),
                    $task->getCompletedByName(),
                    $task->getCompletedByEmail()
                ),
            ]
        );
    }

    public function TaskListChangedPayloadTransformator(Task $task): array
    {
        return $this->getCommonTaskProperties($task);
    }

    private function getCommonTaskProperties(Task $task): array
    {
        $assignee = $task->getAssignee();

        return [
            'id' => $task->getId(),
            'name' => $task->getName(),
            'body' => HTML::toPlainText($task->getBody()),
            'project_id' => $task->getProjectId(),
            'project_name' => $this->getProjectName($task->getProjectId()),
            'task_number' => $task->getTaskNumber(),
            'task_list_id' => $task->getTaskListId(),
            'task_list_name' => $this->getTaskListName($task->getTaskListId()),
            'assignee_id' => $assignee ? $assignee->getId() : 0,
            'assignee_name' => $assignee ? $assignee->getDisplayName() : '',
            'is_important' => $task->getIsImportant(),
            'labels' => $task->getLabelNames(),
            'created_by_id' => $task->getCreatedById(),
            'created_by_name' => $task->getCreatedByName(),
        ];
    }

    private function getProjectName(int $project_id): string
    {
        return $this->getNameFromTable('projects', $project_id);
    }

    private function getTaskListName(int $task_list_id): string
    {
        return $this->getNameFromTable('task_lists', $task_list_id);
    }

    private function getNameFromTable(string $table_name, int $id): string
    {
        return (string) DB::executeFirstCell(
            sprintf('SELECT `name` FROM `%s` WHERE `id` = ?', $table_name),
            $id
        );
    }

    /**
     * Return user's display name from the given arguments.
     *
     * @param  int    $id
     * @param  string $full_name
     * @param  string $email
     * @return string
     */
    private function getUserDisplayName($id, $full_name, $email)
    {
        $display_name = '';

        if ($id) {
            $display_name = Users::getUserDisplayNameById($id);
        }

        if (empty($display_name)) {
            $display_name = Users::getUserDisplayName(
                [
                    'full_name' => $full_name,
                    'email' => $email,
                ]
            );
        }

        return $display_name;
    }
}
