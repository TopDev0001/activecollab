<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use ApplauseReaction;
use BudgetThresholdReachedNotification;
use CompletedParentTaskDependencyNotification;
use HeartReaction;
use IUser;
use NewCalendarEventNotification;
use NewCommentNotification;
use NewDiscussionNotification;
use NewNoteNotification;
use NewProjectNotification;
use NewReactionNotification;
use NewSubtaskNotification;
use NewTaskNotification;
use Notification;
use PartyReaction;
use SubtaskReassignedNotification;
use Task;
use TaskReassignedNotification;
use ThinkingReaction;
use ThumbsDownReaction;
use ThumbsUpReaction;

class PushNotificationPayloadTransformer implements PushNotificationPayloadTransformerInterface
{
    private int $instance_id;

    public function __construct(int $instance_id)
    {
        $this->instance_id = $instance_id;
    }

    public function transform(Notification $notification, IUser $recipient): array
    {
        if ($notification instanceof NewCommentNotification) {
            return $this->transformNewCommentNotification($notification, $recipient);
        }
        if ($notification instanceof NewTaskNotification ||
            $notification instanceof NewSubtaskNotification ||
            $notification instanceof SubtaskReassignedNotification ||
            $notification instanceof TaskReassignedNotification
        ) {
            return $this->transformSubtaskOrTaskAssignment($notification, $recipient);
        }
        if ($notification instanceof NewProjectNotification ||
            $notification instanceof NewDiscussionNotification ||
            $notification instanceof NewNoteNotification
        ) {
            return $this->transformNewObjectNotification($notification, $recipient);
        }
        if ($notification instanceof NewCalendarEventNotification) {
            return $this->transformNewCalendarEventNotification($notification, $recipient);
        }
        if ($notification instanceof CompletedParentTaskDependencyNotification) {
            return $this->transformCompletedParentTaskDependencyNotification($notification, $recipient);
        }
        if ($notification instanceof NewReactionNotification) {
            return $this->transformNewReactionNotification($notification, $recipient);
        }
        if ($notification instanceof BudgetThresholdReachedNotification) {
            return $this->transformBudgetThresholdReachedNotification($notification, $recipient);
        }

        return [
            'title' => lang('New notification', null, $recipient->getLanguage()),
            'body' => '',
            'data' => array_merge($notification->jsonSerialize(), ['instance_id' => $this->instance_id]),
        ];
    }

    private function transformNewCommentNotification(NewCommentNotification $notification, IUser $recipient): array
    {
        $comment = $notification->getComment();
        $mentions = array_flip($comment->getNewMentions());
        /** @var Task $parent */
        $parent = $notification->getParent();
        $title = $this->createExcerpt($parent->getName(), '', 40);

        $body = '';
        if ($comment) {
            if (count($mentions) > 0 && isset($mentions[$recipient->getId()])) {
                $body = lang(':user mentioned you',
                        ['user' => $notification->getSender() ? $notification->getSender()->getDisplayName(true) : 'Someone'],
                        true,
                        $recipient->getLanguage()
                    ).$this->createExcerpt($comment->getPlainTextBody());
            } else {
                $body = lang(':user commented',
                        ['user' => $notification->getSender() ? $notification->getSender()->getDisplayName(true) : 'Someone'],
                        true,
                        $recipient->getLanguage()
                    ).$this->createExcerpt($comment->getPlainTextBody());
            }
        }

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'id' => $notification->getId(),
                'sender_id' => $notification->getSenderId(),
                'class' => get_class($notification),
                'instance_id' => $this->instance_id,
                'comment_id' => $comment->getId(),
                'parent_id' => $notification->getParentId(),
                'parent_type' => $notification->getParentType(),
                'project_id' => $parent->getProjectId(),
                'url_path' => $parent->getUrlPath(),
            ],
        ];
    }

    private function transformSubtaskOrTaskAssignment($notification, IUser $recipient)
    {
        /** @var Task $parent */
        $parent = $notification->getParent();
        $type = 'task';
        $excerpt = '';
        $data = [
            'id' => $notification->getId(),
            'sender_id' => $notification->getSenderId(),
            'class' => get_class($notification),
            'instance_id' => $this->instance_id,
            'parent_id' => $notification->getParentId(),
            'parent_type' => $notification->getParentType(),
            'project_id' => $parent->getProjectId(),
            'url_path' => $parent->getUrlPath(),
        ];
        if ($notification instanceof NewSubtaskNotification ||
            $notification instanceof SubtaskReassignedNotification) {
            $type = 'subtask';
            $data['subtask_id'] = $notification->getSubtask()->getId();
            $excerpt = $this->createExcerpt($notification->getSubtask()->getName());
        }

        return [
            'title' => $this->createExcerpt($parent->getName(), '', 40),
            'body' => lang(':user assigned you a :type', [
                    'user' => $notification->getSender() ? $notification->getSender()->getDisplayName(true) : 'Someone',
                    'type' => $type,
                ], true, $recipient->getLanguage()).$excerpt,
            'data' => $data,
        ];
    }

    private function transformNewObjectNotification($notification, IUser $recipient)
    {
        $parent = $notification->getParent();
        $type = strtolower($notification->getParentType());
        $project_id = $notification instanceof NewProjectNotification ? $notification->getParentId() : $parent->getProjectId();

        return [
            'title' => $this->createExcerpt($parent->getName(), '', 40),
            'body' => lang(':user invited you to :type', [
                    'user' => $notification->getSender() ? $notification->getSender()->getDisplayName(true) : 'Someone',
                    'type' => $type,
                ], true, $recipient->getLanguage()),
            'data' => [
                'id' => $notification->getId(),
                'sender_id' => $notification->getSenderId(),
                'class' => get_class($notification),
                'instance_id' => $this->instance_id,
                'parent_id' => $notification->getParentId(),
                'parent_type' => $notification->getParentType(),
                'project_id' => $project_id,
                'url_path' => $parent->getUrlPath(),
            ],
        ];
    }

    private function transformNewCalendarEventNotification(NewCalendarEventNotification $notification, IUser $recipient)
    {
        /** @var \CalendarEvent $parent */
        $parent = $notification->getParent();
        $date = $parent->getStartsOn() ? $parent->getStartsOn()->formatDateForUser($recipient) : '';
        $time = $parent->getStartsOnTime() ? $parent->getStartsOn()->formatTimeForUser($recipient) : '';

        $calendar_name = $parent->getCalendar()->getName();
        $body = "$date $time\r\n$calendar_name";

        return [
            'title' => $this->createExcerpt($parent->getName(), '', 40),
            'body' => $body,
            'data' => [
                'id' => $notification->getId(),
                'sender_id' => $notification->getSenderId(),
                'class' => get_class($notification),
                'instance_id' => $this->instance_id,
                'parent_id' => $notification->getParentId(),
                'parent_type' => $notification->getParentType(),
                'calendar_id' => $parent->getCalendarId(),
                'url_path' => $parent->getUrlPath(),
            ],
        ];
    }

    private function transformCompletedParentTaskDependencyNotification(
        CompletedParentTaskDependencyNotification $notification,
        IUser $recipient
    ) {
        /** @var Task $parent */
        $parent = $notification->getParent();
        $project_id = $parent->getProjectId();

        return [
            'title' => $this->createExcerpt($parent->getName(), '', 40),
            'body' => lang('Parent task completed', [], true, $recipient->getLanguage()),
            'data' => [
                'id' => $notification->getId(),
                'sender_id' => $notification->getSenderId(),
                'class' => get_class($notification),
                'instance_id' => $this->instance_id,
                'parent_id' => $notification->getParentId(),
                'parent_type' => $notification->getParentType(),
                'project_id' => $project_id,
                'url_path' => $parent->getUrlPath(),
            ],
        ];
    }

    private function transformNewReactionNotification(NewReactionNotification $notification, IUser $recipient)
    {
        /** @var Task $parent */
        $parent = $notification->getParent();
        $project_id = $parent->getProjectId();
        $type_class = $notification->getReactionType();
        switch ($type_class) {
            case HeartReaction::class:
                $type = '❤️';

                break;
            case ThumbsUpReaction::class:
                $type = '👍';

                break;
            case ThumbsDownReaction::class:
                $type = '👎';

                break;
            case PartyReaction::class:
                $type = '🎉';

                break;
            case ApplauseReaction::class:
                $type = '👏';

                break;
            case ThinkingReaction::class:
                $type = '🤔';

                break;
            default:
                $type = '😀';

                break;
        }

        return [
            'title' => $this->createExcerpt($parent->getName(), '', 40),
            'body' => lang(':user reacted with :type', [
                'user' => $notification->getSender() ? $notification->getSender()->getDisplayName(true) : 'Someone',
                'type' => $type,
            ], true, $recipient->getLanguage()),
            'data' => [
                'id' => $notification->getId(),
                'sender_id' => $notification->getSenderId(),
                'class' => get_class($notification),
                'instance_id' => $this->instance_id,
                'parent_id' => $notification->getParentId(),
                'parent_type' => $notification->getParentType(),
                'project_id' => $project_id,
                'url_path' => $parent->getUrlPath(),
            ],
        ];
    }

    private function transformBudgetThresholdReachedNotification(BudgetThresholdReachedNotification $notification, IUser $recipient)
    {
        $parent = $notification->getParent();

        return [
            'title' => $this->createExcerpt($parent->getName(), '', 40),
            'body' => lang('budget alert', [], true, $recipient->getLanguage()),
            'data' => [
                'id' => $notification->getId(),
                'class' => get_class($notification),
                'instance_id' => $this->instance_id,
                'parent_id' => $notification->getParentId(),
                'parent_type' => $notification->getParentType(),
                'project_id' => $parent->getId(),
                'url_path' => $parent->getUrlPath(),
            ],
        ];
    }

    public static function createExcerpt($text = '', $prepend = ': ', $len = 80): string
    {
        $text = mb_substr($text, 0);
        if (strlen($text) > 0) {
            $text = $prepend.$text;
        }
        $titleLength = strlen($text);
        if ($titleLength < $len) {
            return $text;
        }
        do {
            ++$len;
            $charAtPosition = substr($text, $len, 1);
        } while ($len < $titleLength && ' ' != $charAtPosition);

        return substr($text, 0, $len).'...';
    }
}
