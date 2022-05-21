<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\Webhooks\Transformator;

use ActivityLog;
use AvailabilityRecord;
use AvailabilityType;
use Comment;
use Conversation;
use ConversationUser;
use DataObject;
use DB;
use Discussion;
use Exception;
use Expense;
use Message;
use Note;
use NotificationRecipient;
use Project;
use ProjectTemplate;
use Reaction;
use Reminder;
use Stopwatch;
use Subtask;
use Task;
use TaskList;
use Team;
use TimeRecord;
use User;
use WebhookPayloadTransformator;

class PusherSocketPayloadTransformator extends WebhookPayloadTransformator implements SocketPayloadTransformatorInterface
{
    public function shouldTransform(string $url): bool
    {
        return strpos($url, 'api.pusherapp.com') !== false;
    }

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
            'ConversationCreated',
            'ConversationUpdated',
            'ConversationDeleted',
            'ConversationUserCreated',
            'ConversationUserUpdated',
            'ConversationUserDeleted',
            'MessageCreated',
            'MessageUpdated',
            'MessageDeleted',
            'CommentCreated',
            'CommentUpdated',
            'CommentMovedToTrash',
            'CommentRestoredFromTrash',
            'ReactionCreated',
            'ReactionDeleted',
            'TaskCreated',
            'TaskUpdated',
            'TaskCompleted',
            'TaskReopened',
            'TaskListChanged',
            'TaskMoveToTrash',
            'TaskRestoredFromTrash',
            'TaskReordered',
            'SubtaskCreated',
            'SubtaskUpdated',
            'SubtaskReordered',
            'SubtaskCompleted',
            'SubtaskReopened',
            'SubtaskMoveToTrash',
            'SubtaskRestoredFromTrash',
            'SubtaskDeleted',
            'TaskListCreated',
            'TaskListInserted',
            'TaskListUpdated',
            'TaskListReordered',
            'TaskListMoveToTrash',
            'TaskListRestoredFromTrash',
            'TaskListCompleted',
            'TaskListReopened',
            'StopwatchCreated',
            'StopwatchUpdated',
            'StopwatchDeleted',
            'TimeRecordCreated',
            'TimeRecordUpdated',
            'TimeRecordMoveToTrash',
            'TimeRecordRestoredFromTrash',
            'ExpenseCreated',
            'ExpenseUpdated',
            'AvailabilityRecordCreated',
            'AvailabilityRecordUpdated',
            'AvailabilityRecordDeleted',
            'AvailabilityTypeCreated',
            'AvailabilityTypeUpdated',
            'AvailabilityTypeDeleted',
            'ProjectMembershipGranted',
            'ProjectMembershipRevoked',
            'ProjectCreated',
            'ProjectUpdated',
            'ProjectCompleted',
            'ProjectReopened',
            'ProjectMoveToTrash',
            'ProjectRestoredFromTrash',
            'ProjectTemplateCreated',
            'ProjectTemplateUpdated',
            'ProjectTemplateMoveToTrash',
            'ProjectTemplateRestoredFromTrash',
            'OwnerCreated',
            'MemberCreated',
            'ClientCreated',
            'UserUpdated',
            'UserMovedToTrash',
            'UserRestoredFromTrash',
            'UserMovedToArchive',
            'UserRestoredFromArchive',
            'NotificationRecipientCreated',
            'NotificationRecipientUpdated',
            'NotificationRecipientDeleted',
            'NoteCreated',
            'NoteUpdated',
            'NoteMoveToTrash',
            'DiscussionCreated',
            'DiscussionUpdated',
            'DiscussionMoveToTrash',
            'ActivityLogCreated',
            'ReminderCreated',
            'ReminderDeleted',
            'TeamDeleted',
        ];
    }

    public function ProjectMembershipGrantedPayloadTransformator(Project $project)
    {
        return $project->jsonSerialize();
    }

    public function ProjectMembershipRevokedPayloadTransformator(Project $project)
    {
        return $project->jsonSerialize();
    }

    public function ProjectCreatedPayloadTransformator(Project $project): array
    {
        return $project->jsonSerialize();
    }

    public function ProjectUpdatedPayloadTransformator(Project $project)
    {
        return $project->jsonSerialize();
    }

    public function ProjectCompletedPayloadTransformator(Project $project)
    {
        return $project->jsonSerialize();
    }

    public function ProjectReopenedPayloadTransformator(Project $project)
    {
        return $project->jsonSerialize();
    }

    public function ProjectMoveToTrashPayloadTransformator(Project $project)
    {
        return $project->jsonSerialize();
    }

    public function ProjectRestoredFromTrashPayloadTransformator(Project $project)
    {
        return $project->jsonSerialize();
    }

    public function ProjectTemplateCreatedPayloadTransformator(ProjectTemplate $projectTemplate): array
    {
        return $projectTemplate->jsonSerialize();
    }

    public function ProjectTemplateUpdatedPayloadTransformator(ProjectTemplate $projectTemplate)
    {
        return $projectTemplate->jsonSerialize();
    }

    public function ProjectTemplateMoveToTrashPayloadTransformator(ProjectTemplate $projectTemplate)
    {
        return $projectTemplate->jsonSerialize();
    }

    public function ProjectTemplateRestoredFromTrashPayloadTransformator(ProjectTemplate $projectTemplate)
    {
        return $projectTemplate->jsonSerialize();
    }

    public function OwnerCreatedPayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    public function MemberCreatedPayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    public function ClientCreatedPayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    public function UserUpdatedPayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    public function UserMovedToTrashPayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    public function UserRestoredFromTrashPayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    public function UserMovedToArchivePayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    public function UserRestoredFromArchivePayloadTransformator(User $user)
    {
        return $this->userPayload($user);
    }

    private function userPayload(User $user)
    {
        return $user->jsonSerialize();
    }

    public function AvailabilityRecordCreatedPayloadTransformator(AvailabilityRecord $record)
    {
        return $this->availabilityRecordPayload($record);
    }

    public function AvailabilityRecordUpdatedPayloadTransformator(AvailabilityRecord $record)
    {
        return $this->availabilityRecordPayload($record);
    }

    public function AvailabilityRecordDeletedPayloadTransformator(AvailabilityRecord $record)
    {
        return $this->availabilityRecordPayload($record);
    }

    private function availabilityRecordPayload(AvailabilityRecord $record)
    {
        return [
            'id' => $record->getId(),
            'availability_type_id' => $record->getAvailabilityTypeId(),
            'start_date' => $record->getStartDate(),
            'end_date' => $record->getEndDate(),
            'user_id' => $record->getUserId(),
            'duration' => $record->getDuration(),
            'created_by_id' => $record->getCreatedById(),
            'created_on' => $record->getCreatedOn(),
            'updated_on' => $record->getUpdatedOn(),
        ];
    }

    public function AvailabilityTypeCreatedPayloadTransformator(AvailabilityType $type)
    {
        return $this->availabilityTypePayload($type);
    }

    public function AvailabilityTypeUpdatedPayloadTransformator(AvailabilityType $type)
    {
        return $this->availabilityTypePayload($type);
    }

    public function AvailabilityTypeDeletedPayloadTransformator(AvailabilityType $type)
    {
        return $this->availabilityTypePayload($type);
    }

    private function availabilityTypePayload(AvailabilityType $type)
    {
        return [
            'id' => $type->getId(),
            'name' => $type->getName(),
            'level' => $type->getLevel(),
            'is_in_use' => $type->isInUse(),
            'created_on' => $type->getCreatedOn(),
            'updated_on' => $type->getUpdatedOn(),
        ];
    }

    public function StopwatchCreatedPayloadTransformator(Stopwatch $stopwatch)
    {
        return $stopwatch->jsonSerialize();
    }

    public function StopwatchUpdatedPayloadTransformator(Stopwatch $stopwatch)
    {
        return $stopwatch->jsonSerialize();
    }

    public function StopwatchDeletedPayloadTransformator(Stopwatch $stopwatch)
    {
        return $stopwatch->jsonSerialize();
    }

    public function TimeRecordCreatedPayloadTransformator(TimeRecord $timeRecord)
    {
        return $timeRecord->jsonSerialize();
    }

    public function TimeRecordUpdatedPayloadTransformator(TimeRecord $timeRecord)
    {
        return $timeRecord->jsonSerialize();
    }

    public function TimeRecordMoveToTrashPayloadTransformator(TimeRecord $timeRecord)
    {
        return $timeRecord->jsonSerialize();
    }

    public function TimeRecordRestoredFromTrashPayloadTransformator(TimeRecord $timeRecord)
    {
        return $timeRecord->jsonSerialize();
    }

    public function ExpenseCreatedPayloadTransformator(Expense $expense)
    {
        return $expense->jsonSerialize();
    }

    public function ExpenseUpdatedPayloadTransformator(Expense $expense)
    {
        return $expense->jsonSerialize();
    }

    public function NotificationRecipientCreatedPayloadTransformator(NotificationRecipient $notification_recipient)
    {
        return $notification_recipient->jsonSerialize();
    }

    public function NotificationRecipientUpdatedPayloadTransformator(NotificationRecipient $notification_recipient)
    {
        return $notification_recipient->jsonSerialize();
    }

    public function NotificationRecipientDeletedPayloadTransformator(NotificationRecipient $notification_recipient)
    {
        return $notification_recipient->jsonSerialize();
    }

    public function NoteCreatedPayloadTransformator(Note $note)
    {
        return $this->NoteUpdatedPayloadTransformator($note);
    }

    public function NoteUpdatedPayloadTransformator(Note $note)
    {
        $data = array_merge(
            $note->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $note->getId(),
                'project_id' => $note->getProjectId(),
                'url' => $note->getUrlPath(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    public function NoteMoveToTrashPayloadTransformator(Note $note)
    {
        return $note->jsonSerialize();
    }

    public function DiscussionCreatedPayloadTransformator(Discussion $discussion)
    {
        return $this->DiscussionUpdatedPayloadTransformator($discussion);
    }

    public function DiscussionUpdatedPayloadTransformator(Discussion $discussion)
    {
        $data = array_merge(
            $discussion->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $discussion->getId(),
                'project_id' => $discussion->getProjectId(),
                'url' => $discussion->getUrlPath(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    public function DiscussionMoveToTrashPayloadTransformator(Discussion $discussion)
    {
        return $discussion->jsonSerialize();
    }

    public function ConversationCreatedPayloadTransformator(Conversation $conversation): array
    {
        return $this->ConversationUpdatedPayloadTransformator($conversation);
    }

    public function ConversationUpdatedPayloadTransformator(Conversation $conversation): array
    {
        $data = array_merge(
            $conversation->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $conversation->getId(),
                'url' => $conversation->getUrlPath(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    public function ConversationDeletedPayloadTransformator(Conversation $conversation): array
    {
        return [
            'id' => $conversation->getId(),
            'is_complete_data' => true,
        ];
    }

    public function ConversationUserCreatedPayloadTransformator(ConversationUser $conversation_user): array
    {
        return $this->conversationUserPayload($conversation_user);
    }

    public function ConversationUserUpdatedPayloadTransformator(ConversationUser $conversation_user): array
    {
        return $this->conversationUserPayload($conversation_user);
    }

    public function ConversationUserDeletedPayloadTransformator(ConversationUser $conversation_user): array
    {
        return $this->conversationUserPayload($conversation_user);
    }

    private function conversationUserPayload(ConversationUser $conversation_user): array
    {
        return array_merge(
            $conversation_user->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );
    }

    public function MessageCreatedPayloadTransformator(Message $message)
    {
        $data = array_merge(
            $message->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $message->getId(),
                'conversation_id' => $message->getConversationId(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    public function MessageUpdatedPayloadTransformator(Message $message)
    {
        return $this->MessageCreatedPayloadTransformator($message);
    }

    private function MessageDeletedPayloadTransformator(Message $message)
    {
        return [
            'id' => $message->getId(),
            'conversation_id' => $message->getConversationId(),
            'is_complete_data' => true,
        ];
    }

    public function CommentCreatedPayloadTransformator(Comment $comment): array
    {
        return $this->commentPayload($comment);
    }

    public function CommentUpdatedPayloadTransformator(Comment $comment)
    {
        return $this->commentPayload($comment);
    }

    public function CommentMovedToTrashPayloadTransformator(Comment $comment)
    {
        return $this->commentPayload($comment);
    }

    public function CommentRestoredFromTrashPayloadTransformator(Comment $comment)
    {
        return $this->commentPayload($comment);
    }

    private function commentPayload(Comment $comment)
    {
        $data = $comment->jsonSerialize();
        $data['is_complete_data'] = true;

        $parent = $comment->getParent();

        if ($parent instanceof Task) {
            $data['task'] = $parent->jsonSerialize();
        }

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $comment->getId(),
                'url_path' => $comment->getUrlPath(),
                'parent_id' => $comment->getParentId(),
                'parent_type' => $comment->getParentType(),
                'project_id' => $comment->getProjectId(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    /**
     * Transform payload when reaction is created.
     *
     * @return array
     */
    public function ReactionCreatedPayloadTransformator(Reaction $reaction)
    {
        return $this->reactionPayload($reaction);
    }

    /**
     * Transform payload when reaction is deleted.
     *
     * @return array
     */
    public function ReactionDeletedPayloadTransformator(Reaction $reaction)
    {
        return $this->reactionPayload($reaction);
    }

    /**
     * Reaction payload.
     *
     * @return array
     */
    private function reactionPayload(Reaction $reaction)
    {
        return [
            'id' => $reaction->getId(),
            'class' => get_class($reaction),
            'parent_id' => $reaction->getParentId(),
            'parent_type' => $reaction->getParentType(),
            'created_by_id' => $reaction->getCreatedById(),
            'created_by_name' => $reaction->getCreatedByName(),
            'created_by_email' => $reaction->getCreatedByEmail(),
            'created_on' => $reaction->getCreatedOn(),
        ];
    }

    private function TaskCreatedPayloadTransformator(Task $task)
    {
        $data = array_merge(
            $task->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $task->getId(),
                'project_id' => $task->getProjectId(),
                'url' => $task->getUrlPath(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    private function TaskUpdatedPayloadTransformator(Task $task)
    {
        return $this->TaskCreatedPayloadTransformator($task);
    }

    private function TaskCompletedPayloadTransformator(Task $task)
    {
        return [
            'id' => $task->getId(),
            'project_id' => $task->getProjectId(),
            'is_completed' => $task->isCompleted(),
            'open_dependencies' => $task->getOpenDependencies(),
            'completed_on' => $task->getCompletedOn(),
            'is_complete_data' => true,
        ];
    }

    private function TaskReopenedPayloadTransformator(Task $task)
    {
        return $this->TaskCompletedPayloadTransformator($task);
    }

    private function TaskListChangedPayloadTransformator(Task $task)
    {
        return [
            'id' => $task->getId(),
            'project_id' => $task->getProjectId(),
            'task_list_id' => $task->getTaskListId(),
            'position' => $task->getPosition(),
            'is_complete_data' => true,
        ];
    }

    private function TaskMoveToTrashPayloadTransformator(Task $task)
    {
        return [
            'id' => $task->getId(),
            'project_id' => $task->getProjectId(),
            'is_trashed' => $task->getIsTrashed(),
            'is_complete_data' => true,
        ];
    }

    private function TaskRestoredFromTrashPayloadTransformator(Task $task)
    {
        return $this->TaskMoveToTrashPayloadTransformator($task);
    }

    private function TaskReorderedPayloadTransformator(Task $task)
    {
        $ordered_ids = DB::executeFirstColumn(
            'SELECT t.id FROM tasks t WHERE t.task_list_id = ? ORDER BY t.position ASC',
            $task->getTaskListId()
        );

        return [
            'task_list_id' => $task->getTaskListId(),
            'ordered_task_ids' => $ordered_ids,
        ];
    }

    private function SubtaskCreatedPayloadTransformator(Subtask $subtask)
    {
        return $this->getSubtaskPayload($subtask);
    }

    private function SubtaskUpdatedPayloadTransformator(Subtask $subtask)
    {
        return $this->getSubtaskPayload($subtask);
    }

    private function SubtaskReorderedPayloadTransformator(Subtask $subtask)
    {
        $ordered_ids = DB::executeFirstColumn(
            'SELECT `id` FROM `subtasks` WHERE `task_id` = ? ORDER BY `position` ASC',
            $subtask->getTaskId()
        );

        return [
            'task_id' => $subtask->getTaskId(),
            'ordered_subtask_ids' => $ordered_ids,
            'is_complete_data' => true,
        ];
    }

    private function SubtaskCompletedPayloadTransformator(Subtask $subtask)
    {
        return $this->getSubtaskPayload($subtask);
    }

    private function SubtaskReopenedPayloadTransformator(Subtask $subtask)
    {
        return $this->getSubtaskPayload($subtask);
    }

    private function SubtaskMoveToTrashPayloadTransformator(Subtask $subtask)
    {
        return $this->getSubtaskPayload($subtask);
    }

    private function SubtaskRestoredFromTrashPayloadTransformator(Subtask $subtask)
    {
        return $this->getSubtaskPayload($subtask);
    }

    private function SubtaskDeletedPayloadTransformator(Subtask $subtask)
    {
        $task = $subtask->getTask();

        return [
            'id' => $subtask->getId(),
            'task_id' => $task->getId(),
            'project_id' => $task->getProjectId(),
            'is_complete_data' => true,
        ];
    }

    private function getSubtaskPayload(Subtask $subtask)
    {
        $task = $subtask->getTask();

        $data = array_merge(
            $subtask->jsonSerialize(),
            [
                'project_id' => $task->getProjectId(),
                'is_complete_data' => true,
                'task' => $task->jsonSerialize(),
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $subtask->getId(),
                'task_id' => $task->getId(),
                'project_id' => $task->getProjectId(),
                'url' => $subtask->getUrlPath(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    private function TaskListCreatedPayloadTransformator(TaskList $task_list): array
    {
        $data = array_merge(
            $task_list->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $task_list->getId(),
                'project_id' => $task_list->getProjectId(),
                'url' => $task_list->getUrlPath(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    private function TaskListInsertedPayloadTransformator(TaskList $task_list): array
    {
        return [
            'single' => $task_list,
            'order' => DB::executeFirstColumn(
                'SELECT id FROM task_lists WHERE project_id = ? AND is_trashed = ? AND completed_on IS NULL ORDER BY position ASC, id DESC',
                $task_list->getProjectId(),
                false
            ),
        ];
    }

    private function TaskListUpdatedPayloadTransformator(TaskList $task_list): array
    {
        return $this->TaskListCreatedPayloadTransformator($task_list);
    }

    private function TaskListReorderedPayloadTransformator(TaskList $task_list): array
    {
        return [
            'ordered_task_list_ids' => DB::executeFirstColumn(
                'SELECT id FROM task_lists WHERE project_id = ? AND completed_on IS NULL ORDER BY position ASC',
                $task_list->getProjectId()
            ),
        ];
    }

    private function TaskListMoveToTrashPayloadTransformator(TaskList $task_list): array
    {
        return [
            'id' => $task_list->getId(),
            'name' => $task_list->getName(),
            'project_id' => $task_list->getProjectId(),
            'is_trashed' => $task_list->getIsTrashed(),
            'is_complete_data' => true,
        ];
    }

    private function TaskListRestoredFromTrashPayloadTransformator(TaskList $task_list): array
    {
        return $this->TaskListMoveToTrashPayloadTransformator($task_list);
    }

    private function TaskListCompletedPayloadTransformator(TaskList $task_list): array
    {
        return [
            'id' => $task_list->getId(),
            'name' => $task_list->getName(),
            'project_id' => $task_list->getProjectId(),
            'is_completed' => $task_list->isCompleted(),
            'is_complete_data' => true,
        ];
    }

    private function TaskListReopenedPayloadTransformator(TaskList $task_list): array
    {
        return $this->TaskListCompletedPayloadTransformator($task_list);
    }

    public function ActivityLogCreatedPayloadTransformator(ActivityLog $activity_log): array
    {
        $data = array_merge(
            $activity_log->jsonSerialize(),
            [
                'is_complete_data' => true,
            ]
        );

        if ($this->calculateDataSize($data) >= self::PUSHER_PAYLOAD_LIMIT) {
            return [
                'id' => $activity_log->getId(),
                'url' => $activity_log->getUrlPath(),
                'is_complete_data' => false,
            ];
        } else {
            return $data;
        }
    }

    public function ReminderCreatedPayloadTransformator(Reminder $reminder): array
    {
        return $reminder->jsonSerialize();
    }

    public function ReminderDeletedPayloadTransformator(Reminder $reminder): array
    {
        return $this->ReminderCreatedPayloadTransformator($reminder);
    }

    public function TeamDeletedPayloadTransformator(Team $team): array
    {
        return [
            'id' => $team->getId(),
            'is_complete_data' => true,
        ];
    }

    /**
     * Calculate size of data array.
     *
     * @param $data
     * @return int
     */
    public function calculateDataSize($data)
    {
        $serialized = serialize(json_encode($data));
        if (function_exists('mb_strlen')) {
            return mb_strlen($serialized, '8bit');
        } else {
            return strlen($serialized);
        }
    }
}
