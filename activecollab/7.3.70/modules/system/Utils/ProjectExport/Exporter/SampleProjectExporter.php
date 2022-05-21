<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\ProjectExport\Exporter;

use ActiveCollab\Module\System\Utils\ProjectExport\ProjectExport;
use ActiveCollab\Module\Tasks\Utils\TaskDependenciesResolver\TaskDependenciesResolver;
use Angie\Inflector;
use Attachment;
use ConfigOptions;
use DateTimeValue;
use DateValue;
use DB;
use Discussion;
use Discussions;
use Expense;
use ExpenseCategories;
use ExpenseCategory;
use File;
use FileCreateError;
use Files;
use IAttachments;
use IComments;
use IReactions;
use ISubscriptions;
use ITracking;
use Label;
use Note;
use Notes;
use Project;
use Reactions;
use RuntimeException;
use Subtask;
use Task;
use TaskList;
use TaskLists;
use Tasks;
use TimeRecord;
use User;

class SampleProjectExporter extends ProjectExport
{
    /**
     * @var string
     */
    private $projects_list_path;

    public function __construct(
        Project $project,
        User $user,
        DateTimeValue $changes_since = null,
        $include_file_locations = false,
        $work_folder_path = '',
        $projects_list_path = ''
    )
    {
        if (!$user->isOwner()) {
            throw new RuntimeException('Sample project export requires user as owner');
        }

        $this->projects_list_path = $projects_list_path;

        parent::__construct(
            $project,
            $user,
            $changes_since,
            $include_file_locations,
            $work_folder_path
        );
    }

    public function export(bool $delete_work_folder = true): string
    {
        $file_path = $this->getFilePath();

        if (!is_file($file_path)) {
            $this->prepareWorkFolder($this->getWorkFolderPath());
            $this->prepareWorkFolder($this->getWorkFolderPath() . '/files');
            $this->prepareWorkFolder($this->getWorkFolderPath() . '/attachments');
            $this->writeData();
            $this->writeSampleProjectsList();

            return $file_path;
        }

        return $file_path;
    }

    public function getFilePath(): string
    {
        return $this->getWorkFolderPath() . '/template.json';
    }

    public function getWorkFolderPath(): string
    {
        if ($this->work_folder_path === '') {
            $this->work_folder_path = sprintf(
                '%s/modules/system/resources/sample_projects/%s',
                APPLICATION_PATH,
                $this->getProjectKeyName()
            );
        }

        return $this->work_folder_path;
    }

    protected function getWorkFolderName(): string
    {
        return $this->getProjectKeyName();
    }

    public function getProjectsListFilePath()
    {
        if ($this->projects_list_path === '') {
            $this->projects_list_path = sprintf(
                '%s/modules/system/resources/sample_projects/sample_projects_list.json',
                APPLICATION_PATH
            );
        }

        return $this->projects_list_path;
    }

    private function writeSampleProjectsList()
    {
        $file_path = $this->getProjectsListFilePath();

        if (is_file($file_path)) {
            $content = json_decode(
                file_get_contents($file_path),
                true
            );

            if (empty($content) || !is_array($content)) {
                $content = [];
            }

            if (array_key_exists($this->getProjectKeyName(), $content)) {
                $content[$this->getProjectKeyName()]['timestamp'] = DateTimeValue::now()->getTimestamp();
            } else {
                $content[$this->getProjectKeyName()] = [
                    'name' => $this->project->getName(),
                    'is_preselected' => false,
                    'is_active' => false,
                    'timestamp' => DateTimeValue::now()->getTimestamp(),
                ];
            }
        } else {
            $content = [];

            $content[$this->getProjectKeyName()] = [
                'name' => $this->project->getName(),
                'is_preselected' => false,
                'is_active' => false,
                'timestamp' => DateTimeValue::now()->getTimestamp(),
            ];
        }

        if ($file_handle = fopen($file_path, 'a')) {
            file_put_contents($file_path, ''); // clear file first
            fwrite(
                $file_handle,
                json_encode(
                    $content,
                    JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE
                )
            );
            fclose($file_handle);
        } else {
            throw new FileCreateError($file_path);
        }
    }

    private function getProjectKeyName()
    {
        return Inflector::slug($this->project->getName());
    }

    private function writeData()
    {
        if ($file_handle = fopen($this->getFilePath(), 'a')) {
            $data = [];

            $this->projectData($data);
            $this->configOptionValuesData($data);

            fwrite(
                $file_handle,
                json_encode(
                    $data,
                    JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE
                )
            );
            fclose($file_handle);
        } else {
            throw new FileCreateError($this->getFilePath());
        }
    }

    private function projectData(array &$data)
    {
        $data['name'] = $this->project->getName();
        $data['members'] = [];
        $data['category_id'] = $this->project->getCategoryId();
        $data['created_on'] = 0;
        $data['created_by_name'] = $this->project->getCreatedByName();
        $data['created_by_email'] = $this->project->getCreatedByEmail();
        $data['body'] = $this->project->getBody();
        $data['currency_id'] = $this->project->getCurrencyId();
        $data['email'] = $this->project->getMailToProjectEmail();
        $data['is_tracking_enabled'] = $this->project->getIsTrackingEnabled();
        $data['is_client_reporting_enabled'] = $this->project->getIsClientReportingEnabled();
        $data['budget'] = $this->project->getBudget();

        $data['task_lists'] = $this->taskListData();
        $data['task_dependencies'] = $this->taskDependencies();
        $data['discussions'] = $this->discussionsData();
        $data['notes'] = $this->notesData();
        $data['time_records'] = $this->getTimeRecords($this->project);
        $data['expenses'] = $this->getExpenses($this->project);
        $data['files'] = $this->filesData();

        $data['job_types'] = $this->getGlobalJobTypes();
        $data['expense_categories'] = $this->getGlobalExpenseCategories();
    }

    private function taskListData()
    {
        $ids = $this->getTaskListIds();
        $results = [];

        if (!empty($ids)) {
            /** @var TaskList[] $task_lists */
            $task_lists = TaskLists::findByIds($ids);

            foreach ($task_lists as $task_list) {
                $start_on = $task_list->getStartOn() instanceof DateValue
                    ? $this->setRelativeDate($task_list->getStartOn())
                    : null;
                $due_on = $task_list->getDueOn() instanceof DateValue
                    ? $this->setRelativeDate($task_list->getDueOn())
                    : null;

                $results[] = [
                    'name' => $task_list->getName(),
                    'completed_on' => $task_list->getCompletedOn() ? 0 : null,
                    'completed_by_name' => $task_list->getCompletedByName(),
                    'completed_by_email' => $task_list->getCompletedByEmail(),
                    'created_by_name' => $task_list->getCreatedByName(),
                    'created_by_email' => $task_list->getCreatedByEmail(),
                    'is_completed' => $task_list->isCompleted(),
                    'project_id' => null,
                    'created_on' => 0,
                    'start_on' => $start_on,
                    'due_on' => $due_on,
                    'position' => $task_list->getPosition(),
                    'tasks' => $this->tasksData($task_list),
                ];
            }
        }

        return $results;
    }

    private function taskDependencies()
    {
        $results = [];

        $task_dependencies = (new TaskDependenciesResolver($this->user))->getProjectDependencies($this->project->getId());

        if (count($task_dependencies)) {
            foreach ($task_dependencies as $task_dependency) {
                $parent_task = Tasks::findById($task_dependency['parent_id']);
                $child_task = Tasks::findById($task_dependency['child_id']);

                $results[] = [
                    'parent_name' => trim($parent_task->getName()),
                    'child_name' => trim($child_task->getName()),
                ];
            }
        }

        return $results;
    }

    private function tasksData(TaskList $task_list)
    {
        /** @var Task[] $tasks */
        $tasks = Tasks::find([
            'conditions' => [
                'project_id = ? AND task_list_id = ?',
                $this->project->getId(),
                $task_list->getId(),
            ],
        ]);
        $results = [];

        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                $start_on = $task->getStartOn() instanceof DateValue
                    ? $this->setRelativeDate($task->getStartOn())
                    : null;
                $due_on = $task->getDueOn() instanceof DateValue
                    ? $this->setRelativeDate($task->getDueOn())
                    : null;

                $results[] = [
                    'name' => $task->getName(),
                    'fake_assignee_name' => $task->getAssignee() ? $task->getAssignee()->getName() : null,
                    'fake_assignee_email' => $task->getAssignee() ? $task->getAssignee()->getEmail() : null,
                    'attachments' => $this->getAttachments($task),
                    'labels' => $this->getTaskLabels($task),
                    'is_hidden_from_clients' => $task->getIsHiddenFromClients(),
                    'body' => $task->getBody(),
                    'body_mode' => 'break-line',
                    'is_important' => $task->getIsImportant(),
                    'estimate' => $task->getEstimate(),
                    'completed_on' => $task->getCompletedOn() ? 0 : null,
                    'completed_by_name' => $task->getCompletedByName(),
                    'completed_by_email' => $task->getCompletedByEmail(),
                    'is_completed' => $task->isCompleted(),
                    'created_on' => 0,
                    'created_by_name' => $task->getCreatedByName(),
                    'created_by_email' => $task->getCreatedByEmail(),
                    'start_on' => $start_on,
                    'due_on' => $due_on,
                    'job_type_id' => $task->getJobTypeId() ? $task->getJobTypeId() : 0,
                    'position' => $task->getPosition(),
                    'subtasks' => $this->subtasksData($task),
                    'comments' => $this->getComments($task),
                    'time_records' => $this->getTimeRecords($task),
                    'expenses' => $this->getExpenses($task),
                    'subscribers' => $this->getSubscribers($task),
                ];
            }
        }

        return $results;
    }

    private function getSubscribers(ISubscriptions $object)
    {
        $subscribers = $object->getSubscribers();
        $results = [];

        if (!empty($subscribers)) {
            foreach ($subscribers as $subscriber) {
                $results[] = [
                    'user_name' => $subscriber->getName(),
                    'user_email' => $subscriber->getEmail(),
                ];
            }
        }

        return $results;
    }

    private function getTimeRecords(ITracking $object)
    {
        /** @var TimeRecord[] $time_records */
        $time_records = $object->getTimeRecords($this->user);
        $results = [];

        if (!empty($time_records)) {
            foreach ($time_records as $time_record) {
                $record_date = $time_record->getRecordDate() instanceof DateValue
                    ? $this->setRelativeDate($time_record->getRecordDate())
                    : null;

                $results[] = [
                    'user_name' => $time_record->getUserName(),
                    'user_email' => $time_record->getUserEmail(),
                    'created_on' => 0,
                    'created_by_name' => $time_record->getCreatedByName(),
                    'created_by_email' => $time_record->getCreatedByEmail(),
                    'value' => $time_record->getValue(),
                    'record_date' => $record_date,
                    'job_type_name' => $time_record->getJobTypeName(),
                    'billable_status' => $time_record->getBillableStatus(),
                    'summary' => $time_record->getSummary(),
                ];
            }
        }

        return $results;
    }

    private function getExpenses(ITracking $object)
    {
        /** @var Expense[] $expenses */
        $expenses = $object->getExpenses($this->user);
        $results = [];

        if (!empty($expenses)) {
            foreach ($expenses as $expense) {
                $record_date = $expense->getRecordDate() instanceof DateValue
                    ? $this->setRelativeDate($expense->getRecordDate())
                    : null;

                /** @var ExpenseCategory $expense_category */
                $expense_category = ExpenseCategories::findById($expense->getCategoryId());

                $results[] = [
                    'user_name' => $expense->getUserName(),
                    'user_email' => $expense->getUserEmail(),
                    'created_on' => 0,
                    'created_by_name' => $expense->getCreatedByName(),
                    'created_by_email' => $expense->getCreatedByEmail(),
                    'value' => $expense->getValue(),
                    'record_date' => $record_date,
                    'billable_status' => $expense->getBillableStatus(),
                    'summary' => $expense->getSummary(),
                    'category_name' => $expense_category
                        ? $expense_category->getName()
                        : ExpenseCategories::getDefault()->getName(),
                ];
            }
        }

        return $results;
    }

    private function getTaskLabels(Task $task)
    {
        /** @var Label[] $labels */
        $labels = $task->getLabels();
        $results = [];

        if (!empty($labels)) {
            foreach ($labels as $label) {
                $results[] = [
                    'name' => $label->getName(),
                    'color' => $label->getColor(),
                    'is_default' => $label->getIsDefault(),
                    'position' => $label->getPosition(),
                    'is_global' => $label->getIsGlobal(),
                ];
            }
        }

        return $results;
    }

    private function getAttachments(IAttachments $object)
    {
        /** @var Attachment[] $attachments */
        $attachments = $object->getAttachments();
        $results = [];

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment->getPath())) {
                    $this->moveFile($attachment);

                    $results[] = [
                        'name' => $attachment->getName(),
                        'md5' => $attachment->getMd5(),
                        'mime_type' => $attachment->getMimeType(),
                        'created_on' => 0,
                        'created_by_name' => $attachment->getCreatedByName(),
                        'created_by_email' => $attachment->getCreatedByEmail(),
                        'is_hidden_from_clients' => $attachment->getIsHiddenFromClients(),
                    ];
                }
            }
        }

        return $results;
    }

    private function moveFile($file)
    {
        $folder = $file instanceof Attachment ? 'attachments' : 'files';

        copy($file->getPath(), $this->getWorkFolderPath() . '/' . $folder . '/' . $file->getMd5());
    }

    private function subtasksData(Task $task)
    {
        /** @var Subtask[] $subtasks */
        $subtasks = $task->getSubtasks();
        $results = [];

        if (!empty($subtasks)) {
            foreach ($subtasks as $subtask) {
                $results[] = [
                    'body' => $subtask->getBody(),
                    'fake_assignee_name' => $subtask->getAssignee() ? $subtask->getAssignee()->getName() : null,
                    'fake_assignee_email' => $subtask->getAssignee() ? $subtask->getAssignee()->getEmail() : null,
                    'created_on' => 0,
                    'created_by_name' => $subtask->getCreatedByName(),
                    'created_by_email' => $subtask->getCreatedByEmail(),
                    'position' => $subtask->getPosition(),
                    'completed_on' => $subtask->getCompletedOn() ? 0 : null,
                    'completed_by_name' => $subtask->getCompletedByName(),
                    'completed_by_email' => $subtask->getCompletedByEmail(),
                ];
            }
        }

        return $results;
    }

    private function discussionsData()
    {
        $ids = $this->getDiscussionIds();
        $results = [];

        if (!empty($ids)) {
            /** @var Discussion[] $discussions */
            $discussions = Discussions::findByIds($ids);
            foreach ($discussions as $discussion) {
                $results[] = [
                    'name' => $discussion->getName(),
                    'body' => $discussion->getBody(),
                    'body_mode' => 'break-line',
                    'is_hidden_from_clients' => $discussion->getIsHiddenFromClients(),
                    'created_on' => 0,
                    'created_by_name' => $discussion->getCreatedByName(),
                    'created_by_email' => $discussion->getCreatedByEmail(),
                    'comments' => $this->getComments($discussion),
                    'attachments' => $this->getAttachments($discussion),
                    'subscribers' => $this->getSubscribers($discussion),
                ];
            }
        }

        return $results;
    }

    private function getComments(IComments $object)
    {
        $comments = $object->getComments();
        $results = [];

        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $results[] = [
                    'body' => $comment->getBody(),
                    'body_mode' => 'break-line',
                    'attachments' => $this->getAttachments($comment),
                    'created_by_name' => $comment->getCreatedByName(),
                    'created_by_email' => $comment->getCreatedByEmail(),
                    'created_on' => 0,
                    'reactions' => $this->getReactions($comment),
                ];
            }
        }

        return $results;
    }

    private function getReactions(IReactions $parent)
    {
        $reactions = Reactions::getDetailsByParent($parent);

        $results = [];

        if (!empty($reactions)) {
            foreach ($reactions as $reaction) {
                $results[] = [
                    'type' => $reaction['class'],
                    'created_on' => 0,
                    'created_by_name' => $reaction['created_by_name'],
                    'created_by_email' => $reaction['created_by_email'],
                ];
            }
        }

        return $results;
    }

    private function notesData()
    {
        $ids = $this->getNoteIds();
        $results = [];

        if (!empty($ids)) {
            /** @var Note[] $notes */
            $notes = Notes::findByIds($ids);

            foreach ($notes as $note) {
                $results[] = [
                    'name' => $note->getName(),
                    'body' => $note->getBody(),
                    'body_mode' => 'break-line',
                    'is_hidden_from_clients' => $note->getIsHiddenFromClients(),
                    'created_on' => 0,
                    'created_by_name' => $note->getCreatedByName(),
                    'created_by_email' => $note->getCreatedByEmail(),
                    'comments' => $this->getComments($note),
                    'attachments' => $this->getAttachments($note),
                    'subscribers' => $this->getSubscribers($note),
                ];
            }
        }

        return $results;
    }

    private function filesData()
    {
        $ids = $this->getFileIds();
        $results = [];

        if (!empty($ids)) {
            /** @var File[] $files */
            $files = Files::findByIds($ids);

            foreach ($files as $file) {
                if (file_exists($file->getPath())) {
                    $this->moveFile($file);
                    $results[] = [
                        'name' => $file->getName(),
                        'mime_type' => $file->getMimeType(),
                        'created_on' => 0,
                        'created_by_name' => $file->getCreatedByName(),
                        'created_by_email' => $file->getCreatedByEmail(),
                        'is_hidden_from_clients' => $file->getIsHiddenFromClients(),
                        'md5' => $file->getMd5(),
                    ];
                }
            }
        }

        return $results;
    }

    private function configOptionValuesData(array &$data)
    {
        $config_options = ConfigOptions::getValuesFor(
            [
                'display_mode_projects',
                'display_mode_project_files',
                'display_mode_project_tasks',
                'display_mode_project_time',
                'sort_mode_project_notes',
                'show_visual_editor_toolbar',
                'task_time_tracking_enabled',
                'expense_tracking_enabled',
            ],
            $this->user
        );

        $data['config_option_values'] = [
            'display_mode_projects' => $config_options['display_mode_projects'],
            'display_mode_project_files' => $config_options['display_mode_project_files'],
            'display_mode_project_time' => $config_options['display_mode_project_time'],
            'sort_mode_project_notes' => $config_options['sort_mode_project_notes'],
            'show_visual_editor_toolbar' => $config_options['show_visual_editor_toolbar'],
            'task_time_tracking_enabled' => $config_options['task_time_tracking_enabled'],
            'expense_tracking_enabled' => $config_options['expense_tracking_enabled'],
        ];

        if (isset($config_options['display_mode_project_tasks']) && is_array($config_options['display_mode_project_tasks'])) {
            $data['config_option_values']['display_mode_project_tasks'] = $config_options['display_mode_project_tasks'][$this->project->getId()];
        } else {
            $data['config_option_values']['display_mode_project_tasks'] = ConfigOptions::getValue('display_mode_project_tasks');
        }
    }

    /**
     * Get global job types.
     *
     * @return array
     */
    private function getGlobalJobTypes()
    {
        $job_types = DB::execute('SELECT name, default_hourly_rate, is_default FROM job_types');

        $result = [];

        if (!empty($job_types)) {
            foreach ($job_types as $job_type) {
                $result[] = $job_type;
            }
        }

        return $result;
    }

    /**
     * Get global expense categories.
     *
     * @return array
     */
    private function getGlobalExpenseCategories()
    {
        $expense_categories = DB::execute('SELECT name, is_default FROM expense_categories');

        $result = [];

        if (!empty($expense_categories)) {
            foreach ($expense_categories as $expense_category) {
                $result[] = $expense_category;
            }
        }

        return $result;
    }

    private function setRelativeDate(DateValue $date)
    {
        $is_negative = DateValue::now()->getTimestamp() > $date->getTimestamp();

        $num = DateValue::now()->daysBetween($date);

        return $is_negative ? -1 * $num : $num;
    }
}
