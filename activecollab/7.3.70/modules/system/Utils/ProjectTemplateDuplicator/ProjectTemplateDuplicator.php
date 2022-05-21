<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ProjectTemplateDuplicator;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\Module\Files\Utils\FileDuplicator\FileDuplicatorInterface;
use DateTimeValue;
use DB;
use ProjectTemplate;
use ProjectTemplateDiscussion;
use ProjectTemplateFile;
use ProjectTemplateNote;
use ProjectTemplateRecurringTask;
use ProjectTemplateTask;
use ProjectTemplateTaskList;
use User;

class ProjectTemplateDuplicator implements ProjectTemplateDuplicatorInterface
{
    private $file_duplicator;
    private $current_timestamp;

    public function __construct(
        FileDuplicatorInterface $file_duplicator,
        CurrentTimestampInterface $current_timestamp
    )
    {
        $this->file_duplicator = $file_duplicator;
        $this->current_timestamp = $current_timestamp;
    }

    public function duplicate(
        ProjectTemplate $template,
        User $by,
        string $new_template_name = null
    ): ProjectTemplate
    {
        $template_copy = null;

        DB::transact(
            function () use ($template, $by, $new_template_name, &$template_copy) {
                $now = new DateTimeValue($this->current_timestamp->getCurrentTimestamp());

                $template_copy = $this->createTemplateCopy($template, $by, $new_template_name, $now);

                $this->copyMembers($template, $template_copy);
                $task_lists_id_map = $this->copyTaskLists($template, $template_copy, $now);
                $tasks_id_map = $this->copyTasks($template, $template_copy, $task_lists_id_map, $now);
                $this->copySubtasks($template, $template_copy, $tasks_id_map, $now);
                $this->copyRecurringTasks($template, $template_copy, $now);
                $this->copyDiscussions($template, $template_copy, $now);
                $this->copyFiles($template, $template_copy, $now);
                $note_group_id_map = $this->copyNoteGroups($template, $template_copy, $now);
                $this->copyNotes($template, $template_copy, $note_group_id_map, $now);
            }
        );

        return $template_copy;
    }

    private function createTemplateCopy(
        ProjectTemplate $template,
        User $by,
        ?string $new_template_name,
        DateTimeValue $now
    ): ProjectTemplate
    {
        /** @var ProjectTemplate $template_copy */
        $template_copy = $template->copy(false);
        $template_copy->setCreatedBy($by);
        $template_copy->setCreatedOn($now);
        $template_copy->setUpdatedOn($now);

        if ($new_template_name) {
            $template_copy->setName($new_template_name);
        } else {
            $template_copy->setName('');
        }

        $template_copy->save();

        return $template_copy;
    }

    private function copyMembers(
        ProjectTemplate $template,
        ProjectTemplate $template_copy
    ): void
    {
        $template_members = $template->getMembers();

        if (!empty($template_members)) {
            $template_copy->addMembers($template_members);
        }
    }

    private function copyTaskLists(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        DateTimeValue $now
    ): array
    {
        $task_lists_id_map = [];

        $task_lists = $template->getTaskLists();

        if (!empty($task_lists)) {
            foreach ($task_lists as $task_list) {
                /** @var ProjectTemplateTaskList $task_list_copy */
                $task_list_copy = $task_list->copy(false);

                $task_list_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $task_list_copy->save();

                $task_lists_id_map[$task_list->getId()] = $task_list_copy->getId();
            }
        }

        return $task_lists_id_map;
    }

    public function copyTasks(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        array $task_lists_id_map,
        DateTimeValue $now
    ): array
    {
        $tasks_id_map = [];

        $tasks = $template->getTasks();

        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                /** @var ProjectTemplateTask|\IAttachments $task_copy */
                $task_copy = $task->copy(false);

                $task_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $task_copy->setAdditionalProperty(
                    'task_list_id',
                    $task_lists_id_map[$task->getTaskListId()] ?? null
                );

                $task_copy->save();

                if ($task->countAttachments()) {
                    $task->cloneAttachmentsTo($task_copy);
                }

                if ($task->countLabels()) {
                    $task->cloneLabelsTo($task_copy);
                }

                $tasks_id_map[$task->getId()] = $task_copy->getId();
            }
        }

        return $tasks_id_map;
    }

    public function copySubtasks(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        array $tasks_id_map,
        DateTimeValue $now
    ): void
    {
        $subtasks = $template->getSubtasks();

        if (!empty($subtasks)) {
            foreach ($subtasks as $subtask) {
                if (!array_key_exists($subtask->getTaskId(), $tasks_id_map)) {
                    continue;
                }

                /** @var \ProjectTemplateSubtask $subtask_copy */
                $subtask_copy = $subtask->copy(false);

                $subtask_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $subtask_copy->setAdditionalProperty(
                    'task_id',
                    $tasks_id_map[$subtask->getTaskId()] ?? null
                );

                $subtask_copy->save();
            }
        }
    }

    public function copyRecurringTasks(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        DateTimeValue $now
    ): void
    {
        $recurring_tasks = $template->getRecurringTasks();

        if (!empty($recurring_tasks)) {
            foreach ($recurring_tasks as $recurring_task) {
                /** @var ProjectTemplateRecurringTask $recurring_task_copy */
                $recurring_task_copy = $recurring_task->copy(false);

                $recurring_task_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $recurring_task_copy->save();

                if ($recurring_task->countAttachments()) {
                    $recurring_task->cloneAttachmentsTo($recurring_task_copy);
                }

                if ($recurring_task->countLabels()) {
                    $recurring_task->cloneLabelsTo($recurring_task_copy);
                }
            }
        }
    }

    public function copyDiscussions(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        DateTimeValue $now
    ): void
    {
        $discussions = $template->getDiscussions();

        if (!empty($discussions)) {
            foreach ($discussions as $discussion) {
                /** @var ProjectTemplateDiscussion $discussion_copy */
                $discussion_copy = $discussion->copy(false);

                $discussion_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $discussion_copy->save();

                if ($discussion->countAttachments()) {
                    $discussion->cloneAttachmentsTo($discussion_copy);
                }
            }
        }
    }

    public function copyFiles(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        DateTimeValue $now
    ): void
    {
        $files = $template->getFiles();

        if (!empty($files)) {
            foreach ($files as $file) {
                /** @var ProjectTemplateFile $file_copy */
                $file_copy = $file->copy(false);

                $file_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $new_location = $this->file_duplicator->duplicate($file, $file->getFileType());

                if ($new_location) {
                    $file_copy->setLocation($new_location);
                }

                $file_copy->save();
            }
        }
    }

    private function copyNoteGroups(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        DateTimeValue $now
    ): array
    {
        $note_group_id_map = [];

        $note_groups = $template->getNoteGroups();

        if (!empty($note_groups)) {
            foreach ($note_groups as $note_group) {
                /** @var \ProjectTemplateNoteGroup $note_group_copy */
                $note_group_copy = $note_group->copy(false);

                $note_group_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $note_group_copy->save();

                $note_group_id_map[$note_group->getId()] = $note_group_copy->getId();
            }
        }

        return $note_group_id_map;
    }

    public function copyNotes(
        ProjectTemplate $template,
        ProjectTemplate $template_copy,
        array $note_group_id_map,
        DateTimeValue $now
    ): void
    {
        $notes = $template->getNotes();

        if (!empty($notes)) {
            foreach ($notes as $note) {
                /** @var ProjectTemplateNote|\IAttachments $note_copy */
                $note_copy = $note->copy(false);

                $note_copy->setAttributes(
                    [
                        'template_id' => $template_copy->getId(),
                        'created_on' => $now,
                        'updated_on' => $now,
                    ]
                );

                $note_copy->setAdditionalProperty(
                    'note_group_id',
                    $note_group_id_map[$note->getNoteGroupId()] ?? null
                );

                $note_copy->save();

                if ($note->countAttachments()) {
                    $note->cloneAttachmentsTo($note_copy);
                }
            }
        }
    }
}
