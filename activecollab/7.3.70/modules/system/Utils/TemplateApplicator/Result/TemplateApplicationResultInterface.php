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
use JsonSerializable;
use Note;
use NoteGroup;
use RecurringTask;
use Subtask;
use Task;
use TaskDependency;
use TaskList;

interface TemplateApplicationResultInterface extends JsonSerializable
{
    public function addTaskList(TaskList $task_list): void;
    public function addTask(Task $task): void;
    public function addSubtask(Subtask $subtask): void;
    public function addTaskDependency(TaskDependency $task_dependency): void;
    public function addRecurringTask(RecurringTask $recurring_task): void;
    public function addNoteGroup(NoteGroup $note_group): void;
    public function addNote(Note $note): void;
    public function addDiscussion(Discussion $discussion): void;
    public function addFile(File $file): void;
}
