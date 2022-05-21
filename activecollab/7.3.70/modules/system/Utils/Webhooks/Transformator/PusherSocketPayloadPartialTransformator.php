<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Webhooks\Transformator;

use DataObject;
use Exception;
use Task;
use WebhookPayloadTransformator;

class PusherSocketPayloadPartialTransformator extends WebhookPayloadTransformator implements SocketPayloadTransformatorInterface
{
    public function transform(string $event_type, DataObject $payload): ?array
    {
        if (!in_array($event_type, $this->getSupportedEvents())) {
            return null;
        }

        $transformator = $event_type . 'PayloadTransformator';

        if (method_exists($this, $transformator)) {
            return $this->$transformator($payload);
        } else {
            throw new Exception("Transformator method {$transformator} not implemented");
        }
    }

    public function getSupportedEvents(): array
    {
        return [
            'TaskCreated',
            'TaskUpdated',
            'TaskCompleted',
            'TaskReopened',
            'TaskMoveToTrash',
            'TaskRestoredFromTrash',
        ];
    }

    private function TaskCreatedPayloadTransformator(Task $task)
    {
        return [
            'id' => $task->getId(),
            'project_id' => null,
            'name' => null,
            'assignee_id' => $task->getAssigneeId(),
            'start_on' => $task->getStartOn(),
            'due_on' => $task->getDueOn(),
            'estimate' => $task->getEstimate(),
            'is_completed' => $task->isCompleted(),
            'is_trashed' => $task->getIsTrashed(),
            'is_complete_data' => true,
        ];
    }

    private function TaskUpdatedPayloadTransformator(Task $task)
    {
        return $this->TaskCreatedPayloadTransformator($task);
    }

    private function TaskCompletedPayloadTransformator(Task $task)
    {
        return $this->TaskCreatedPayloadTransformator($task);
    }

    private function TaskReopenedPayloadTransformator(Task $task)
    {
        return $this->TaskCompletedPayloadTransformator($task);
    }

    private function TaskMoveToTrashPayloadTransformator(Task $task)
    {
        return $this->TaskCreatedPayloadTransformator($task);
    }

    private function TaskRestoredFromTrashPayloadTransformator(Task $task)
    {
        return $this->TaskMoveToTrashPayloadTransformator($task);
    }
}
