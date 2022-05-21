<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\Webhooks\Transformator;

use Angie\HTML;
use Client;
use ConfigOptions;
use DataObject;
use Exception;
use Expense;
use const FORMAT_DATE;
use function lang;
use Language;
use Languages;
use Member;
use Owner;
use Project;
use Task;
use TimeRecord;
use User;
use WebhookPayloadTransformator;

/**
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class SlackWebhookPayloadTransformator extends WebhookPayloadTransformator
{
    /**
     * @var Language
     */
    private $language;

    /**
     * Escape particular characters (Slack requirement).
     *
     * @param  string $text
     * @return string
     */
    private function escape($text)
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }

    /**
     * Make a link in Slack format.
     *
     * @param  string $url
     * @param  string $title
     * @return string
     */
    private function link($url, $title)
    {
        return '<' . $url . '|' . $this->escape($title) . '>';
    }

    public function shouldTransform(string $url): bool
    {
        return strpos($url, 'hooks.slack.com') !== false;
    }

    public function transform(string $event_type, DataObject $payload): ?array
    {
        if (!in_array($event_type, $this->getSupportedEvents())) {
            return null;
        }

        $transformator = $this->getTransformatorMethod($event_type);
        $this->language = Languages::findDefault();

        if (!method_exists(self::class, $transformator)) {
            throw new Exception("Transformator method {$transformator} not implemented");
        }

        return $this->$transformator($payload);
    }

    /**
     * Return a name of payload transformator method name.
     *
     * @param $event_type
     * @return string
     */
    private function getTransformatorMethod($event_type)
    {
        return $event_type . 'PayloadTransformator';
    }

    /**
     * Return a list of event types which this transformator supports.
     */
    public function getSupportedEvents(): array
    {
        return [
            'ProjectCreated',
            'OwnerCreated',
            'MemberCreated',
            'ClientCreated',
            'UserAcceptedInvitation',
            'TaskCreated',
            'TaskCompleted',
            'TaskListChanged',
            'TaskListChangedFromReorder',
            'TimeRecordCreated',
            'ExpenseCreated',
        ];
    }

    public function ProjectCreatedPayloadTransformator(Project $project): array
    {
        $payload['attachments'][] = [
            'fallback' => $this->escape(
                lang(':created_by created the ":project_name" project', [
                    'created_by' => $project->getCreatedBy()->getDisplayName(true),
                    'project_name' => $project->getName(),
                ], true, $this->language)
            ),
            'pretext' => lang(':created_by created а project', [
                'created_by' => $this->link($project->getCreatedBy()->getViewUrl(), $project->getCreatedBy()->getDisplayName(true)),
            ], false, $this->language
            ),
            'title' => $this->escape($project->getName()),
            'title_link' => $project->getViewUrl(),
            'text' => HTML::toPlainText($project->getBody()),
            'color' => '#4581F2',
        ];

        $payload['attachments'][0]['fields'][] = [
            'title' => lang('Client', null, null, $this->language),
            'value' => $this->escape($project->getCompany()->getName()),
            'short' => true,
        ];

        if (!empty($project->getLabel())) {
            $payload['attachments'][0]['fields'][] = [
                'title' => lang('Label', null, null, $this->language),
                'value' => $this->escape($project->getLabel()->getName()),
                'short' => true,
            ];
        }

        if (!empty($project->getCategory())) {
            $payload['attachments'][0]['fields'][] = [
                'title' => lang('Category', null, null, $this->language),
                'value' => $this->escape($project->getCategory()->getName()),
                'short' => true,
            ];
        }

        return $payload;
    }

    /**
     * Transform webhook payload for OwnerCreated event.
     *
     * @return array
     */
    public function OwnerCreatedPayloadTransformator(Owner $owner)
    {
        return [
            'text' => lang(':by sent an invitation to :to', [
                'by' => $this->link($owner->getCreatedBy()->getViewUrl(), $owner->getCreatedBy()->getDisplayName(true)),
                'to' => $owner->getEmail(),
            ], null, $this->language),
        ];
    }

    /**
     * Transform webhook payload for MemberCreated event.
     *
     * @return array
     */
    public function MemberCreatedPayloadTransformator(Member $member)
    {
        return [
            'text' => lang(':by sent an invitation to :to', [
                'by' => $this->link($member->getCreatedBy()->getViewUrl(), $member->getCreatedBy()->getDisplayName(true)),
                'to' => $member->getEmail(),
            ], null, $this->language),
        ];
    }

    /**
     * Transform webhook payload for ClientCreated event.
     *
     * @return array
     */
    public function ClientCreatedPayloadTransformator(Client $client)
    {
        return [
            'text' => lang(':by sent an invitation to :to', [
                'by' => $this->link($client->getCreatedBy()->getViewUrl(), $client->getCreatedBy()->getDisplayName(true)),
                'to' => $client->getEmail(),
            ], null, $this->language),
        ];
    }

    /**
     * Transform webhook payload for UserAcceptedInvitation event.
     *
     * @return array
     */
    public function UserAcceptedInvitationPayloadTransformator(User $user)
    {
        return [
            'text' => lang(':user_link (:user_email) has joined ActiveCollab', [
                'user_link' => $this->link($user->getViewUrl(), $user->getDisplayName()),
                'user_email' => $user->getEmail(),
            ], null, $this->language),
        ];
    }

    /**
     * Transform webhook payload for TaskCreated event.
     */
    public function TaskCreatedPayloadTransformator(Task $task): array
    {
        $payload['attachments'][] = [
            'fallback' => $this->escape(
                lang(':created_by created the ":task_name" task', [
                    'created_by' => $task->getCreatedBy()->getDisplayName(true),
                    'task_name' => $task->getName(),
                ], true, $this->language)
            ),
            'pretext' => lang(':created_by created а task', [
                'created_by' => $this->link($task->getCreatedBy()->getViewUrl(), $task->getCreatedBy()->getDisplayName(true)),
            ], false, $this->language
            ),
            'title' => '#' . $task->getTaskNumber() . ': ' . $this->escape($task->getName()),
            'title_link' => $task->getViewUrl(),
            'text' => HTML::toPlainText($task->getBody()),
            'color' => '#4581F2',
        ];

        $payload['attachments'][0]['fields'][] = [
            'title' => lang('Project', null, true, $this->language),
            'value' => $this->link($task->getProject()->getViewUrl(), $task->getProject()->getName()),
            'short' => true,
        ];

        $payload['attachments'][0]['fields'][] = [
            'title' => lang('Task List', null, true, $this->language),
            'value' => $this->escape($task->getTaskList()->getName()),
            'short' => true,
        ];

        if (!empty($task->getAssignee())) {
            $payload['attachments'][0]['fields'][] = [
                'title' => lang('Assignee', null, true, $this->language),
                'value' => $this->link($task->getAssignee()->getViewUrl(), $task->getAssignee()->getName()),
                'short' => true,
            ];
        }

        if (!empty($task->getLabels())) {
            $labels = [];

            foreach ($task->getLabels() as $label) {
                $labels[] = $this->escape($label->getName());
            }

            $payload['attachments'][0]['fields'][] = [
                'title' => lang('Labels', null, true, $this->language),
                'value' => implode(', ', $labels),
                'short' => true,
            ];
        }

        if (!empty($task->getStartOn()) || !empty($task->getDueOn())) {
            $format_date = ConfigOptions::getValue('format_date');

            if (empty($format_date)) {
                $format_date = FORMAT_DATE;
            }

            if (!empty($task->getStartOn()) && !$task->getStartOn()->isSameDay($task->getDueOn())) {
                $payload['attachments'][0]['fields'][] = [
                    'title' => lang('Start on', null, true, $this->language),
                    'value' => $task->getStartOn()->formatUsingStrftime($format_date, null, $this->language),
                    'short' => true,
                ];
            }

            if (!empty($task->getDueOn())) {
                $payload['attachments'][0]['fields'][] = [
                    'title' => lang('Due on', null, true, $this->language),
                    'value' => $task->getDueOn()->formatUsingStrftime($format_date, null, $this->language),
                    'short' => true,
                ];
            }
        }

        if (!empty($task->getEstimate()) && !empty($task->getJobType())) {
            $payload['attachments'][0]['fields'][] = [
                'title' => lang('Estimation', null, true, $this->language),
                'value' => lang(':estimation hours of :job_type', ['estimation' => $task->getEstimate(), 'job_type' => $task->getJobType()->getName()], true, $this->language),
                'short' => true,
            ];
        }

        return $payload;
    }

    public function TaskCompletedPayloadTransformator(Task $task): array
    {
        return [
            'text' => lang(':by completed the :task_link task', [
                'by' => $task->getCompletedBy()->getDisplayName(true),
                'task_link' => $this->link($task->getViewUrl(), '#' . $task->getTaskNumber() . ': ' . $task->getName()),
            ], null, $this->language),
        ];
    }

    public function TaskListChangedPayloadTransformator(Task $task): array
    {
        return [
            'text' => lang(':by moved the :task_link task to :task_list task list', [
                'by' => $task->getUpdatedBy()->getDisplayName(true),
                'task_link' => $this->link($task->getViewUrl(), '#' . $task->getTaskNumber() . ': ' . $task->getName()),
                'task_list' => '*' . $task->getTaskList()->getName() . '*',
            ], null, $this->language),
        ];
    }

    public function TaskListChangedFromReorderPayloadTransformator(Task $task): array
    {
        return $this->TaskListChangedPayloadTransformator($task);
    }

    public function TimeRecordCreatedPayloadTransformator(TimeRecord $time_record): array
    {
        $parent_name = ($time_record->getParent()->getVerboseType() == 'Task')
            ? '#' . $time_record->getParent()->getTaskNumber() . ': ' . $time_record->getParent()->getName()
            : $time_record->getParent()->getName();

        return [
            'text' => lang('Time tracked :time_record_name on :parent_link :parent_type', [
                'time_record_name' => $time_record->getName(),
                'parent_link' => $this->link($time_record->getParent()->getViewUrl(), $parent_name),
                'parent_type' => $time_record->getParent()->getVerboseType(),
            ], null, $this->language),
        ];
    }

    /**
     * Transform webhook payload for ExpenseTracked event.
     *
     * @return array
     */
    public function ExpenseCreatedPayloadTransformator(Expense $expense)
    {
        $parent_name = ($expense->getParent()->getVerboseType() == 'Task')
            ? '#' . $expense->getParent()->getTaskNumber() . ': ' . $expense->getParent()->getName()
            : $expense->getParent()->getName();

        return [
            'text' => lang('Expense tracked :expense_record_name on :parent_link :parent_type', [
                'expense_record_name' => $expense->getName(),
                'parent_link' => $this->link($expense->getParent()->getViewUrl(), $parent_name),
                'parent_type' => $expense->getParent()->getVerboseType(),
            ], null, $this->language),
        ];
    }
}
