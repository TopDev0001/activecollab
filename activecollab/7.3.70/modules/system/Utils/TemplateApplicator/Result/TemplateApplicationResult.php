<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\TemplateApplicator\Result;

use Discussion;
use File;
use Note;
use NoteGroup;
use Project;
use ProjectTemplate;
use RecurringTask;
use Subtask;
use Task;
use TaskDependency;
use TaskList;

class TemplateApplicationResult implements TemplateApplicationResultInterface
{
    private Project $project;
    private ProjectTemplate $project_template;
    private array $task_lists = [];
    private array $tasks = [];
    private array $subtasks = [];
    private array $task_dependencies = [];
    private array $recurring_tasks = [];
    private array $note_groups = [];
    private array $notes = [];
    private array $discussions = [];
    private array $files = [];

    public function __construct(Project $project, ProjectTemplate $project_template)
    {
        $this->project = $project;
        $this->project_template = $project_template;
    }

    public function addTaskList(TaskList $task_list): void
    {
        $this->task_lists[] = $task_list;
    }

    public function addTask(Task $task): void
    {
        $this->tasks[] = $task;
    }

    public function addSubtask(Subtask $subtask): void
    {
        $this->subtasks[] = $subtask;
    }

    public function addTaskDependency(TaskDependency $task_dependency): void
    {
        $this->task_dependencies[] = $task_dependency;
    }

    public function addRecurringTask(RecurringTask $recurring_task): void
    {
        $this->recurring_tasks[] = $recurring_task;
    }

    public function addNoteGroup(NoteGroup $note_group): void
    {
        $this->note_groups[] = $note_group;
    }

    public function addNote(Note $note): void
    {
        $this->notes[] = $note;
    }

    public function addDiscussion(Discussion $discussion): void
    {
        $this->discussions[] = $discussion;
    }

    public function addFile(File $file): void
    {
        $this->files[] = $file;
    }

    public function jsonSerialize(): array
    {
        return [
            'project_id' => $this->project->getId(),
            'project_template_id' => $this->project_template->getId(),
            'task_lists' => $this->task_lists,
            'tasks' => $this->tasks,
            'subtasks' => $this->subtasks,
            'task_dependencies' => $this->task_dependencies,
            'recurring_tasks' => $this->recurring_tasks,
            'note_groups' => $this->note_groups,
            'notes' => $this->notes,
            'discussions' => $this->discussions,
            'files' => $this->files,
        ];
    }
}
