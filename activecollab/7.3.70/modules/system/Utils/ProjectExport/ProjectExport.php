<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ProjectExport;

use Angie\Error;
use AngieApplication;
use Client;
use Comment;
use DateTimeValue;
use DB;
use DirectoryCreateError;
use Discussion;
use DropboxAttachment;
use DropboxFile;
use GoogleDriveAttachment;
use GoogleDriveFile;
use Integrations;
use Note;
use PclZip;
use Project;
use Task;
use User;
use WarehouseAttachment;
use WarehouseFile;
use WarehouseIntegration;

abstract class ProjectExport implements ProjectExportInterface
{
    protected int $timestamp;
    protected Project $project;
    protected User $user;
    protected ?DateTimeValue $changes_since;
    protected bool $include_file_locations;

    private ?array $task_list_ids = null;
    private ?array $task_ids = null;
    private ?array $subtask_ids = null;
    private ?array $discussion_ids = null;
    private ?array $file_ids = null;
    private ?array $note_ids = null;
    private ?array $time_record_ids = null;
    private ?array $expense_ids = null;
    private ?array $comment_ids = null;
    private ?array $attachment_ids = null;
    private ?array $task_labels = null;
    private ?string $user_filter = null;
    protected WarehouseIntegration $warehouse_integration;
    protected string $work_folder_path;

    public function __construct(
        Project $project,
        User $user,
        DateTimeValue $changes_since = null,
        bool $include_file_locations = false,
        string $work_folder_path = ''
    )
    {
        $this->project = $project;
        $this->user = $user;
        $this->changes_since = $changes_since;
        $this->include_file_locations = $include_file_locations;
        $this->work_folder_path = $work_folder_path;

        $this->timestamp = DateTimeValue::now()->getTimestamp();
        $this->warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);
    }

    protected function prepareWorkFolder(string $path): void
    {
        if (!is_dir($path)) {
            $old_umask = umask(0000);
            $folder_created = mkdir($path);
            umask($old_umask);

            if (!$folder_created) {
                throw new DirectoryCreateError($path);
            }
        }
    }

    protected function getTaskListIds(): array
    {
        if ($this->task_list_ids === null) {
            $this->task_list_ids = DB::executeFirstColumn(
                'SELECT `id` FROM `task_lists` WHERE `project_id` = ? ORDER BY `id`',
                $this->project->getId()
            );

            if (empty($this->task_list_ids)) {
                $this->task_list_ids = [];
            }
        }

        return $this->task_list_ids;
    }

    protected function getTaskIds(): array
    {
        if ($this->task_ids === null) {
            $this->task_ids = DB::executeFirstColumn(
                'SELECT id FROM tasks WHERE project_id = ? ' . $this->getUserFilter() . ' ORDER BY id',
                $this->project->getId()
            );

            if (empty($this->task_ids)) {
                $this->task_ids = [];
            }
        }

        return $this->task_ids;
    }

    protected function getUserFilter(): string
    {
        if ($this->user_filter === null) {
            $this->user_filter = $this->user instanceof Client
                ? DB::prepare('AND `is_hidden_from_clients` = ?', false)
                : '';
        }

        return $this->user_filter;
    }

    protected function getSubtaskIds(): array
    {
        if ($this->subtask_ids === null) {
            $this->subtask_ids = DB::executeFirstColumn(
                'SELECT id FROM subtasks WHERE task_id IN (SELECT id FROM tasks WHERE project_id = ? ' . $this->getUserFilter() . ' ORDER BY id)',
                $this->project->getId()
            );

            if (empty($this->subtask_ids)) {
                $this->subtask_ids = [];
            }
        }

        return $this->subtask_ids;
    }

    protected function getDiscussionIds(): array
    {
        if ($this->discussion_ids === null) {
            $this->discussion_ids = DB::executeFirstColumn(
                'SELECT id FROM discussions WHERE project_id = ? ' . $this->getUserFilter() . ' ORDER BY id',
                $this->project->getId()
            );

            if (empty($this->discussion_ids)) {
                $this->discussion_ids = [];
            }
        }

        return $this->discussion_ids;
    }

    protected function getFileIds(): array
    {
        if ($this->file_ids === null) {
            $this->file_ids = DB::executeFirstColumn(
                'SELECT id FROM files WHERE project_id = ? ' . $this->getUserFilter() . ' ORDER BY id',
                $this->project->getId()
            );

            if (empty($this->file_ids)) {
                $this->file_ids = [];
            }
        }

        return $this->file_ids;
    }

    protected function getNoteIds(): array
    {
        if ($this->note_ids === null) {
            $this->note_ids = DB::executeFirstColumn(
                'SELECT id FROM notes WHERE project_id = ? ' . $this->getUserFilter() . ' ORDER BY id',
                $this->project->getId()
            );

            if (empty($this->note_ids)) {
                $this->note_ids = [];
            }
        }

        return $this->note_ids;
    }

    protected function getTimeRecordIds(): array
    {
        if ($this->time_record_ids === null) {
            if ($this->user instanceof Client && !$this->project->getIsClientReportingEnabled()) {
                $this->time_record_ids = [];

                return $this->time_record_ids;
            }

            if ($this->project->getIsTrackingEnabled()) {
                $task_ids = $this->getTaskIds();

                if (count($task_ids)) {
                    $this->time_record_ids = DB::executeFirstColumn(
                        "SELECT id FROM time_records WHERE (parent_type = 'Project' AND parent_id = ?) OR (parent_type = 'Task' AND parent_id IN (?)) " . $this->getFilterByUserRole() . ' ORDER BY id',
                        $this->project->getId(),
                        $task_ids
                    );
                } else {
                    $this->time_record_ids = DB::executeFirstColumn(
                        "SELECT id FROM time_records WHERE parent_type = 'Project' AND parent_id = ? " . $this->getFilterByUserRole() . ' ORDER BY id',
                        $this->project->getId()
                    );
                }
            }
        }

        if (empty($this->time_record_ids)) {
            $this->time_record_ids = [];
        }

        return $this->time_record_ids;
    }

    protected function getExpenseIds(): array
    {
        if ($this->expense_ids === null) {
            if ($this->user instanceof Client && !$this->project->getIsClientReportingEnabled()) {
                $this->expense_ids = [];

                return $this->expense_ids;
            }

            if ($this->project->getIsTrackingEnabled()) {
                $task_ids = $this->getTaskIds();

                if (count($task_ids)) {
                    $this->expense_ids = DB::executeFirstColumn(
                        "SELECT id FROM expenses WHERE (parent_type = 'Project' AND parent_id = ?) OR (parent_type = 'Task' AND parent_id IN (?)) " . $this->getFilterByUserRole() . ' ORDER BY id',
                        $this->project->getId(),
                        $task_ids
                    );
                } else {
                    $this->expense_ids = DB::executeFirstColumn(
                        "SELECT id FROM expenses WHERE parent_type = 'Project' AND parent_id = ? " . $this->getFilterByUserRole() . ' ORDER BY id',
                        $this->project->getId()
                    );
                }
            }
        }

        if (empty($this->expense_ids)) {
            $this->expense_ids = [];
        }

        return $this->expense_ids;
    }

    protected function getCommentIds(): array
    {
        if ($this->comment_ids === null) {
            $conditions = $this->prepareParentConditions();

            if (count($conditions)) {
                $this->comment_ids = DB::executeFirstColumn(
                    'SELECT id FROM comments WHERE ' . implode(' OR ', $conditions)
                );
            }

            if (empty($this->comment_ids)) {
                $this->comment_ids = [];
            }
        }

        return $this->comment_ids;
    }

    protected function getAttachmentIds(): array
    {
        if ($this->attachment_ids === null) {
            $conditions = $this->prepareParentConditions();

            if (count($this->getCommentIds())) {
                $conditions[] = DB::prepare(
                    '(parent_type = ? AND parent_id IN (?))',
                    Comment::class,
                    $this->getCommentIds()
                );
            }

            if (count($conditions)) {
                $this->attachment_ids = DB::executeFirstColumn('SELECT id FROM attachments WHERE ' . implode(' OR ', $conditions));
            }

            if (empty($this->attachment_ids)) {
                $this->attachment_ids = [];
            }
        }

        return $this->attachment_ids;
    }

    protected function getLabelsForTask(int $task_id): array
    {
        if ($this->task_labels === null) {
            $this->task_labels = [];
            $task_ids = $this->getTaskIds();

            if (count($task_ids)) {
                $rows = DB::execute(
                    "SELECT `parent_id`, `label_id` FROM `parents_labels` WHERE `parent_type` = ? AND `parent_id` IN (?) ORDER BY `parent_id`, `label_id`",
                    Task::class,
                    $task_ids
                );

                if ($rows) {
                    foreach ($rows as $row) {
                        if (empty($this->task_labels[$row['parent_id']])) {
                            $this->task_labels[$row['parent_id']] = [];
                        }

                        $this->task_labels[$row['parent_id']][] = $row['label_id'];
                    }
                }
            }
        }

        return isset($this->task_labels[$task_id]) ? $this->task_labels[$task_id] : [];
    }

    private function prepareParentConditions(): array
    {
        $conditions = [];

        if (count($this->getDiscussionIds())) {
            $conditions[] = DB::prepare(
                '(parent_type = ? AND parent_id IN (?))',
                Discussion::class,
                $this->getDiscussionIds()
            );
        }

        if (count($this->getNoteIds())) {
            $conditions[] = DB::prepare(
                '(parent_type = ? AND parent_id IN (?))',
                Note::class,
                $this->getNoteIds()
            );
        }

        if (count($this->getTaskIds())) {
            $conditions[] = DB::prepare(
                '(parent_type = ? AND parent_id IN (?))',
                Task::class,
                $this->getTaskIds()
            );
        }

        return $conditions;
    }

    /**
     * Return filter that filters out time records and expenses that user can see.
     *
     * Clients, project leaders and owners see all time records and expenses in a project. Everyone else see only
     * their-own records.
     *
     * @return string
     */
    protected function getFilterByUserRole()
    {
        return (!($this->user instanceof Client || $this->user->isOwner() || $this->project->isLeader($this->user)))
            ? DB::prepare('AND user_id = ?', $this->user->getId())
            : '';
    }

    protected function unserializeAdditionalProperties(?string $raw_additional_properties): array
    {
        if ($raw_additional_properties) {
            $additional_properties = unserialize($raw_additional_properties);

            if (!is_array($additional_properties)) {
                $additional_properties = [];
            }

            return $additional_properties;
        }

        return [];
    }

    protected function isWarehouseFile(string $type): bool
    {
        return in_array(
            $type,
            [
                WarehouseAttachment::class,
                WarehouseFile::class,
            ]
        );
    }

    protected function isCloudFile(string $type): bool
    {
        return in_array(
            $type,
            [
                DropboxAttachment::class,
                DropboxFile::class,
                GoogleDriveAttachment::class,
                GoogleDriveFile::class,
            ]
        );
    }

    public function getFilePath(): string
    {
        return $this->getWorkFolderPath() . '.zip';
    }

    public function getWorkFolderPath(): string
    {
        if ($this->work_folder_path === '') {
            $this->work_folder_path = sprintf(
                '%s/%s',
                WORK_PATH,
                $this->getWorkFolderName()
            );

            if ($this->changes_since) {
                $this->work_folder_path .= '-' . $this->changes_since->getTimestamp();
            }
        }

        return $this->work_folder_path;
    }

    abstract protected function getWorkFolderName(): string;

    protected function pack(
        string $work_path,
        string $file_path,
        bool $delete_work_folder = true
    ): void
    {
        if (is_file($file_path)) {
            @unlink($file_path);
        }

        $zip = new PclZip($file_path);

        if (!$zip->add(get_files($work_path, null, true), PCLZIP_OPT_REMOVE_PATH, WORK_PATH)) {
            throw new Error('Could not pack files');
        }

        if (DIRECTORY_SEPARATOR != '\\') {
            @chmod($file_path, 0777);
        }

        if ($delete_work_folder) {
            safe_delete_dir($work_path, WORK_PATH);
        }
    }
}
