<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\ProjectImport\Importer;

use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use ActiveCollab\Module\System\Utils\ProjectImport\ProjectImportInterface;
use ActivityLogs;
use Angie\Inflector;
use AngieApplication;
use AnonymousUser;
use Comments;
use ConfigOptions;
use DateTimeValue;
use DateValue;
use DB;
use Discussions;
use Exception;
use ExpenseCategories;
use Expenses;
use Files;
use IActivityLog;
use IAttachments;
use IComments;
use IReactions;
use ISubscriptions;
use ITracking;
use JobType;
use JobTypes;
use Notes;
use Project;
use Projects;
use Reactions;
use RuntimeException;
use Subtasks;
use Task;
use TaskDependencies;
use TaskLabel;
use TaskList;
use TaskLists;
use Tasks;
use TimeRecords;
use UploadedFiles;
use User;

class SampleProjectImport implements ProjectImportInterface
{
    private string $name;
    private User $user;
    private string $work_folder_path;

    public function __construct(
        string $name,
        User $user,
        string $work_folder_path = ''
    )
    {
        if (!$user->isPowerUser()) {
            throw new RuntimeException('Sample project import requires user who can manage projects');
        }

        $this->name = $name;
        $this->user = $user;
        $this->work_folder_path = $work_folder_path;
    }

    public function import(): Project
    {
        $work_folder_path = $this->getWorkFolderPath();

        if (!is_dir($work_folder_path)) {
            throw new RuntimeException("Sample project '{$this->name}' doesn't exist.");
        }

        $file_path = $work_folder_path . 'template.json';

        if (!is_file($file_path)) {
            throw new RuntimeException("Template json file doesn't exist.");
        }

        $template = json_decode(
            file_get_contents($file_path),
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Template json file isn't valid.");
        }

        try {
            DB::beginWork('Creating sample project @ ' . __CLASS__);

            $project = $this->createProject($template);
            $this->createJobTypes($template['job_types']);
            $this->createExpenseCategories($template['expense_categories']);
            $this->createConfigOptions($project, $template['config_option_values']);
            $this->createProjectElements($project, $template);

            DB::commit('Sample project created @ ' . __CLASS__);

            return $project;
        } catch (Exception $e) {
            DB::rollback('Failed to create sample project');

            throw $e;
        }
    }

    public function getWorkFolderPath(): string
    {
        if ($this->work_folder_path === '') {
            $this->work_folder_path = sprintf(
                '%s/modules/system/resources/sample_projects/%s/',
                APPLICATION_PATH,
                Inflector::slug($this->name)
            );
        }

        return $this->work_folder_path;
    }

    private function createJobTypes(array $job_types): void
    {
        $current_job_type_names = DB::executeFirstColumn('SELECT LOWER(name) FROM job_types');

        if (count($job_types) > 0) {
            foreach ($job_types as $job_type) {
                if (!in_array(strtolower($job_type['name']), $current_job_type_names)) {
                    JobTypes::create(
                        [
                            'name' => $job_type['name'],
                            'default_hourly_rate' => $job_type['default_hourly_rate'],
                            'is_default' => $job_type['is_default'],
                        ],
                        true,
                        $this->shouldAnnounceNewObjects()
                    );
                }
            }
        }
    }

    private function createExpenseCategories(array $expense_categories): void
    {
        $current_expense_category_names = DB::executeFirstColumn('SELECT name FROM expense_categories');

        if (count($expense_categories) > 0) {
            foreach ($expense_categories as $expense_category) {
                if (!in_array($expense_category['name'], $current_expense_category_names)) {
                    ExpenseCategories::create(
                        [
                            'name' => $expense_category['name'],
                            'is_default' => $expense_category['is_default'],
                        ],
                        true,
                        $this->shouldAnnounceNewObjects()
                    );
                }
            }
        }
    }

    private function createProject(array $template): Project
    {
        $owner_company = AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();

        $project = Projects::create(
            [
                'is_sample' => true,
                'name' => $template['name'],
                'company_id' => $owner_company->getId(),
                'category_id' => 0,
                'currency_id' => $template['currency_id'],
                'body' => $template['body'],
                'email' => $template['email'],
                'is_tracking_enabled' => $template['is_tracking_enabled'],
                'is_client_reporting_enabled' => $template['is_client_reporting_enabled'],
                'budget' => $template['budget'],
                'created_on' => $this->createDateTime($template['created_on']),
                'created_by_id' => $this->user->getId(), // create project requires 'created_by_id' value
                'created_by_name' => $template['created_by_name'],
                'created_by_email' => $template['created_by_email'],
                'skip_default_task_list' => true,
                'send_invitations' => false,
            ],
            true,
            $this->shouldAnnounceNewObjects()
        );

        $this->updateActivityLog(
            $project,
            $template
        );

        // need to reset 'created_by_id' value
        DB::execute('UPDATE projects SET created_by_id = ? WHERE id = ?', 0, $project->getId());

        return $project;
    }

    private function createConfigOptions(
        Project $project,
        array $options
    ): void
    {
        $existing_project_tasks_mode = ConfigOptions::getValueFor(
            'display_mode_project_tasks',
            $this->user
        );

        if (is_array($existing_project_tasks_mode)) {
            $existing_project_tasks_mode[$project->getId()] = $options['display_mode_project_tasks'];
        } else {
            $existing_project_tasks_mode = [$project->getId() => $options['display_mode_project_tasks']];
        }

        ConfigOptions::setValueFor(
            'display_mode_project_tasks',
            $this->user,
            $existing_project_tasks_mode
        );
    }

    private function createProjectElements(
        Project $project,
        array $template
    ): void
    {
        $task_dependencies = array_key_exists('task_dependencies', $template) && is_array($template['task_dependencies'])
            ? $template['task_dependencies']
            : [];

        $this->createTaskLists($project, $template['task_lists']);
        $this->createTaskDependencies($project, $task_dependencies);
        $this->createDiscussions($project, $template['discussions']);
        $this->createNotes($project, $template['notes']);
        $this->createFiles($project, $template['files']);

        if (ConfigOptions::getValue('task_time_tracking_enabled')) {
            $this->createTimeRecords($project, $template['time_records']);
        }

        if (ConfigOptions::getValue('expense_tracking_enabled')) {
            $this->createExpenses($project, $template['expenses']);
        }
    }

    private function createTaskLists(
        Project $project,
        array $task_lists
    ): void
    {
        if (count($task_lists) > 0) {
            foreach ($task_lists as $task_list_data) {
                $start_on = $task_list_data['start_on'] !== null ? $this->createDate($task_list_data['start_on']) : null;
                $due_on = $task_list_data['due_on'] !== null ? $this->createDate($task_list_data['due_on']) : null;

                $task_list = TaskLists::create(
                    [
                        'name' => $task_list_data['name'],
                        'project_id' => $project->getId(),
                        'start_on' => $start_on,
                        'due_on' => $due_on,
                        'position' => $task_list_data['position'],
                        'created_on' => $this->createDateTime($task_list_data['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $task_list_data['created_by_name'],
                        'created_by_email' => $task_list_data['created_by_email'],
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $this->updateActivityLog(
                    $task_list,
                    $task_list_data
                );

                $this->createTasks($task_list, $task_list_data['tasks']);
            }
        }
    }

    private function createTaskDependencies(Project $project, array $task_dependencies): void
    {
        if (count($task_dependencies)) {
            foreach ($task_dependencies as $task_dependency) {
                $parents = Tasks::findBy(
                    [
                        'project_id' => $project->getId(),
                        'name' => $task_dependency['parent_name'],
                    ]
                );
                $children = Tasks::findBy(
                    [
                        'project_id' => $project->getId(),
                        'name' => $task_dependency['child_name'],
                    ]
                );

                if ($parents && count($parents) === 1 && $children && count($children) === 1) {
                    $parent_task = $parents[0];
                    $child_task = $children[0];

                    TaskDependencies::createDependency($parent_task, $child_task, $this->user);
                }
            }
        }
    }

    private function createTasks(
        TaskList $task_list,
        array $tasks
    ): void
    {
        if (count($tasks) > 0) {
            foreach ($tasks as $task_data) {
                $start_on = $task_data['start_on'] !== null ? $this->createDate($task_data['start_on']) : null;
                $due_on = $task_data['due_on'] !== null ? $this->createDate($task_data['due_on']) : null;

                $attributes = [
                    'project_id' => $task_list->getProjectId(),
                    'task_list_id' => $task_list->getId(),
                    'name' => $task_data['name'],
                    'body' => $task_data['body'],
                    'body_mode' => 'break-line',
                    'fake_assignee_name' => $task_data['fake_assignee_name'],
                    'fake_assignee_email' => $task_data['fake_assignee_email'],
                    'is_hidden_from_clients' => $task_data['is_hidden_from_clients'],
                    'is_important' => $task_data['is_important'],
                    'start_on' => $start_on,
                    'due_on' => $due_on,
                    'position' => $task_data['position'],
                    'created_on' => $this->createDateTime($task_data['created_on']),
                    'created_by_id' => 0,
                    'created_by_name' => $task_data['created_by_name'],
                    'created_by_email' => $task_data['created_by_email'],
                ];

                // create labels if exists
                if (count($task_data['labels'])) {
                    foreach ($task_data['labels'] as $label) {
                        $attributes['labels'][] = $label['name'];
                    }
                }

                $task = Tasks::create(
                    $attributes,
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                // update estimate with default job type value
                if ($task_data['estimate'] > 0) {
                    DB::execute(
                        'UPDATE tasks SET job_type_id = ?, estimate = ? WHERE id = ?',
                        $task_data['job_type_id'],
                        $task_data['estimate'],
                        $task->getId()
                    );
                }

                $this->updateActivityLog(
                    $task,
                    $task_data
                );

                $this->createSubtasks($task, $task_data['subtasks']);
                $this->createAttachments($task, $task_data['attachments']);
                $this->updateLabels($task_data['labels']);
                $this->createComments($task, $task_data['comments']);
                ConfigOptions::getValue('task_time_tracking_enabled') ? $this->createTimeRecords($task, $task_data['time_records']) : null;
                ConfigOptions::getValue('expense_tracking_enabled') ? $this->createExpenses($task, $task_data['expenses']) : null;
                $this->createExpenses($task, $task_data['expenses']);
                $this->createSubscribers($task, $task_data['subscribers']);

                // complete task and subtasks if 'completed_on' is not null
                if ($task_data['completed_on'] !== null) {
                    DB::execute(
                        'UPDATE tasks
                         SET completed_on = ?, completed_by_id = ?, completed_by_name = ?, completed_by_email = ?
                         WHERE id = ?',
                        $this->createDateTime($task_data['completed_on']),
                        0,
                        $task_data['completed_by_name'],
                        $task_data['completed_by_email'],
                        $task->getId()
                    );

                    DB::execute(
                        'UPDATE subtasks
                         SET completed_on = ?, completed_by_id = ?, completed_by_name = ?, completed_by_email = ?
                         WHERE task_id = ? AND completed_on IS NULL',
                        $this->createDateTime($task_data['completed_on']),
                        0,
                        $task_data['completed_by_name'],
                        $task_data['completed_by_email'],
                        $task->getId()
                    );
                }
            }
        }
    }

    private function createSubscribers(
        ISubscriptions $object,
        array $subscribers
    ): void
    {
        if (count($subscribers)) {
            foreach ($subscribers as $subscriber_data) {
                $object->subscribe(
                    new AnonymousUser($subscriber_data['user_name'], $subscriber_data['user_email']),
                    true,
                    true
                );
            }
        }
    }

    private function createComments(
        IComments $object,
        array $comments
    ): void
    {
        if (count($comments)) {
            foreach ($comments as $comment_data) {
                $comment = Comments::create(
                    [
                        'parent_type' => get_class($object),
                        'parent_id' => $object->getId(),
                        'body' => $comment_data['body'],
                        'body_mode' => 'break-line',
                        'created_on' => $this->createDateTime($comment_data['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $comment_data['created_by_name'],
                        'created_by_email' => $comment_data['created_by_email'],
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $reactions = array_key_exists('reactions', $comment_data) && is_array($comment_data['reactions'])
                    ? $comment_data['reactions']
                    : [];

                $this->updateActivityLog($comment, $comment_data);
                $this->createAttachments($comment, $comment_data['attachments']);
                $this->createReactions($comment, $reactions);
            }
        }
    }

    private function createReactions(IReactions $parent, array $reactions): void
    {
        if (count($reactions)) {
            foreach ($reactions as $reaction) {
                Reactions::create(
                    [
                        'type' => $reaction['type'],
                        'parent_type' => get_class($parent),
                        'parent_id' => $parent->getId(),
                        'created_on' => $this->createDateTime($reaction['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $reaction['created_by_name'],
                        'created_by_email' => $reaction['created_by_email'],
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );
            }
        }
    }

    private function createSubtasks(
        Task $task,
        array $subtasks
    ): void
    {
        if (count($subtasks)) {
            foreach ($subtasks as $subtask_data) {
                $attributes = [
                    'task_id' => $task->getId(),
                    'fake_assignee_name' => $subtask_data['fake_assignee_name'],
                    'fake_assignee_email' => $subtask_data['fake_assignee_email'],
                    'body' => $subtask_data['body'],
                    'position' => $subtask_data['position'],
                    'created_on' => $this->createDateTime($subtask_data['created_on']),
                    'created_by_id' => 0,
                    'created_by_name' => $subtask_data['created_by_name'],
                    'created_by_email' => $subtask_data['created_by_email'],
                ];

                $subtask = Subtasks::create(
                    $attributes,
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $this->updateActivityLog(
                    $subtask,
                    $subtask_data
                );

                if ($subtask_data['completed_on'] !== null) {
                    DB::execute(
                        'UPDATE subtasks
                         SET completed_on = ?, completed_by_id = ?, completed_by_name = ?, completed_by_email = ?
                         WHERE id = ?',
                        $this->createDateTime($subtask_data['completed_on']),
                        0,
                        $subtask_data['completed_by_name'],
                        $subtask_data['completed_by_email'],
                        $subtask->getId()
                    );
                }
            }
        }
    }

    private function createAttachments(
        IAttachments $object,
        array $attachments
    ): void
    {
        if (count($attachments) > 0) {
            foreach ($attachments as $attachment_data) {
                $attachment = $object->attachFile(
                    $this->getAttachmentsPath() . '/' . $attachment_data['md5'],
                    $attachment_data['name'],
                    $attachment_data['mime_type'],
                    $this->user
                );

                DB::execute(
                    'UPDATE attachments SET created_by_id = ?, created_by_name = ?, created_by_email = ? WHERE id = ?',
                    0,
                    $attachment_data['created_by_name'],
                    $attachment_data['created_by_email'],
                    $attachment->getId()
                );
            }
        }
    }

    private function updateLabels(array $labels): void
    {
        if (count($labels) > 0) {
            foreach ($labels as $label_data) {
                DB::execute(
                    'UPDATE labels SET color = ?, is_global = ? WHERE type = ? AND name = ?',
                    $label_data['color'],
                    $label_data['is_global'],
                    TaskLabel::class,
                    $label_data['name']
                );
            }
        }
    }

    public function createDiscussions(
        Project $project,
        array $discussions
    ): void
    {
        if (count($discussions) > 0) {
            foreach ($discussions as $discussion_data) {
                $discussion = Discussions::create(
                    [
                        'project_id' => $project->getId(),
                        'name' => $discussion_data['name'],
                        'body' => $discussion_data['body'],
                        'body_mode' => 'break-line',
                        'is_hidden_from_clients' => $discussion_data['is_hidden_from_clients'],
                        'created_on' => $this->createDateTime($discussion_data['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $discussion_data['created_by_name'],
                        'created_by_email' => $discussion_data['created_by_email'],
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $this->updateActivityLog(
                    $discussion,
                    $discussion_data
                );
                $this->createSubscribers($discussion, $discussion_data['subscribers']);
                $this->createAttachments($discussion, $discussion_data['attachments']);
                $this->createComments($discussion, $discussion_data['comments']);
            }
        }
    }

    public function createNotes(
        Project $project,
        array $notes
    ): void
    {
        if (count($notes) > 0) {
            foreach ($notes as $note_data) {
                $note = Notes::create(
                    [
                        'project_id' => $project->getId(),
                        'name' => $note_data['name'],
                        'body' => $note_data['body'],
                        'body_mode' => 'break-line',
                        'is_hidden_from_clients' => $note_data['is_hidden_from_clients'],
                        'created_on' => $this->createDateTime($note_data['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $note_data['created_by_name'],
                        'created_by_email' => $note_data['created_by_email'],
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $this->updateActivityLog(
                    $note,
                    $note_data
                );

                $this->createSubscribers($note, $note_data['subscribers']);
                $this->createAttachments($note, $note_data['attachments']);
                $this->createComments($note, $note_data['comments']);
            }
        }
    }

    public function createTimeRecords(ITracking $object, array $time_records): void
    {
        if (count($time_records) > 0) {
            foreach ($time_records as $time_record_data) {
                /** @var JobType $job_type */
                $job_type = JobTypes::findOneBy('name', $time_record_data['job_type_name']);

                // is_archived must be false, because of validation errors, users can archive job_types
                if ($job_type && $job_type->getIsArchived()) {
                    JobTypes::update($job_type, [
                        'is_archived' => false,
                    ]);
                }

                $job_type_id = $job_type ? $job_type->getId() : JobTypes::getDefaultId();

                $time_record = TimeRecords::create(
                    [
                        'parent_type' => get_class($object),
                        'parent_id' => $object->getId(),
                        'created_on' => $this->createDateTime($time_record_data['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $time_record_data['created_by_name'],
                        'created_by_email' => $time_record_data['created_by_email'],
                        'value' => $time_record_data['value'],
                        'record_date' => $this->createDate($time_record_data['record_date']),
                        'billable_status' => $time_record_data['billable_status'],
                        'summary' => $time_record_data['summary'],
                        'job_type_id' => $job_type_id,
                        'user_id' => 1, // need to set temp value
                        'user_name' => $time_record_data['user_name'],
                        'user_email' => $time_record_data['user_email'],
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $this->updateActivityLog(
                    $time_record,
                    $time_record_data
                );

                // update 'user_id' value to 0
                DB::execute('UPDATE time_records SET user_id = ? WHERE id = ?', 0, $time_record->getId());
            }
        }
    }

    public function createExpenses(ITracking $object, array $expenses): void
    {
        if (count($expenses) > 0) {
            foreach ($expenses as $expense_data) {
                $expense_category = DB::executeFirstRow(
                    'SELECT * FROM expense_categories WHERE name = ? LIMIT 0, 1',
                    $expense_data['category_name']
                );

                // is_archived must be false, because of validation errors, users can archive expense categories
                if ($expense_category && $expense_category['is_archived']) {
                    DB::execute(
                        'UPDATE expense_categories SET is_archived = ? WHERE id = ?',
                        false,
                        $expense_category['id']
                    );
                }

                $expense_category_id = $expense_category ? $expense_category['id'] : ExpenseCategories::getDefaultId();

                $expense = Expenses::create(
                    [
                        'parent_type' => get_class($object),
                        'parent_id' => $object->getId(),
                        'created_on' => $this->createDateTime($expense_data['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $expense_data['created_by_name'],
                        'created_by_email' => $expense_data['created_by_email'],
                        'value' => $expense_data['value'],
                        'record_date' => $this->createDate($expense_data['record_date']),
                        'billable_status' => $expense_data['billable_status'],
                        'summary' => $expense_data['summary'],
                        'category_id' => $expense_category_id,
                        'user_id' => 1, // need to set temp value
                        'user_name' => $expense_data['user_name'],
                        'user_email' => $expense_data['user_email'],
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $this->updateActivityLog(
                    $expense,
                    $expense_data
                );

                // update 'user_id' value to 0
                DB::execute('UPDATE expenses SET user_id = ? WHERE id = ?', 0, $expense->getId());
            }
        }
    }

    public function createFiles(Project $project, array $files): void
    {
        if (count($files) > 0) {
            foreach ($files as $file_data) {
                $path = $this->getFilesPath() . '/' . $file_data['md5'];
                $uploaded_file = UploadedFiles::addFile(
                    $path,
                    $file_data['name'],
                    $file_data['mime_type'],
                    false
                );

                $file = Files::create(
                    [
                        'project_id' => $project->getId(),
                        'name' => $file_data['name'],
                        'mime_type' => $file_data['mime_type'],
                        'created_on' => $this->createDateTime($file_data['created_on']),
                        'created_by_id' => 0,
                        'created_by_name' => $file_data['created_by_name'],
                        'created_by_email' => $file_data['created_by_email'],
                        'is_hidden_from_clients' => $file_data['is_hidden_from_clients'],
                        'md5' => $file_data['md5'],
                        'uploaded_file_code' => $uploaded_file->getCode(),
                    ],
                    true,
                    $this->shouldAnnounceNewObjects()
                );

                $this->updateActivityLog(
                    $file,
                    $file_data
                );
            }
        }
    }

    private function updateActivityLog(
        IActivityLog $parent,
        array $template_object
    ): void
    {
        $parent_conditions = ActivityLogs::parentToCondition($parent);

        DB::execute(
            "UPDATE activity_logs SET created_on = ?, created_by_id = ?, created_by_name = ?, created_by_email = ? WHERE $parent_conditions",
            $this->createDateTime($template_object['created_on']),
            0,
            $template_object['created_by_name'],
            $template_object['created_by_email']
        );
    }

    private function createDateTime($day): string
    {
        return (new DateTimeValue())->addDays($day)->toMySQL();
    }

    private function createDate($day): string
    {
        return (new DateValue())->addDays($day)->toMySQL();
    }

    private function getFilesPath(): string
    {
        return $this->getWorkFolderPath() . 'files';
    }

    private function getAttachmentsPath(): string
    {
        return $this->getWorkFolderPath() . 'attachments';
    }

    private function shouldAnnounceNewObjects(): bool
    {
        return false;
    }
}
