<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate permanently deleted project objects.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigratePermanentlyDeletedProjectObjects extends AngieModelMigration
{
    /**
     * Let the system clean up subtask before we get into this.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePermanentlyDeletedSubtasks');
    }

    /**
     * Migreate up.
     */
    public function up()
    {
        [$projects, $project_objects] = $this->useTables('projects', 'project_objects');

        defined('STATE_DELETED') or define('STATE_DELETED', 0);

        foreach (['time_records', 'expenses'] as $table) {
            if ($this->tableExists($table)) {
                $this->execute('DELETE FROM ' . $this->useTables($table)[0] . ' WHERE parent_type = ? AND state = ?', 'Task', STATE_DELETED);
            }
        }

        if ($rows = $this->execute("SELECT $project_objects.id, $project_objects.type FROM $project_objects LEFT JOIN $projects ON $projects.id = $project_objects.project_id WHERE $project_objects.state = ? OR $projects.state = ?", STATE_DELETED, STATE_DELETED)) {
            $to_drop = [];

            foreach ($rows as $row) {
                if (empty($to_drop[$row['type']])) {
                    $to_drop[$row['type']] = [];
                }

                $to_drop[$row['type']][] = $row['id'];
            }

            if (count($to_drop)) {
                if (isset($to_drop['Task'])) {
                    $escaped_task_ids = DB::escape($to_drop['Task']);

                    $this->dropTimeRecordsAndExpensesForDeletedTasks($escaped_task_ids);
                    $this->cleanUpAssignments($escaped_task_ids);
                    $this->cleanUpSubtasks($escaped_task_ids);
                }

                $parent_conditions = [];

                foreach ($to_drop as $type => $ids) {
                    $parent_conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $type, $ids);
                }

                $parent_conditions = '(' . implode(' OR ', $parent_conditions) . ')';

                $this->cleanUpComments($parent_conditions);
                $this->cleanUpAttachments($parent_conditions);
                $this->cleanUpReminders($parent_conditions);
                $this->cleanUpFavorites($parent_conditions);
                $this->cleanUpSubscriptions($parent_conditions);

                foreach ($to_drop as $ids) {
                    $this->execute("DELETE FROM $project_objects WHERE id IN (?)", $ids);
                }
            }
        }
    }

    /**
     * @param string $escaped_task_ids
     */
    private function dropTimeRecordsAndExpensesForDeletedTasks($escaped_task_ids)
    {
        foreach (['time_records', 'expenses'] as $table) {
            if ($this->tableExists($table)) {
                $this->execute('DELETE FROM ' . $this->useTables($table)[0] . " WHERE parent_type = 'Task' AND parent_id IN ($escaped_task_ids)");
            }
        }
    }

    /**
     * @param string $escaped_task_ids
     */
    private function cleanUpAssignments($escaped_task_ids)
    {
        $this->execute('DELETE FROM ' . $this->useTables('assignments')[0] . " WHERE parent_type = 'Task' AND parent_id IN ($escaped_task_ids)");
    }

    /**
     * @param string $escaped_task_ids
     */
    private function cleanUpSubtasks($escaped_task_ids)
    {
        $subtasks = $this->useTables('subtasks')[0];

        if ($rows = $this->execute("SELECT id, type FROM $subtasks WHERE parent_type = 'Task' AND parent_id IN ($escaped_task_ids)")) {
            $subtask_ids = [];
            $subtask_parent_conditions = [];

            foreach ($rows as $row) {
                $subtask_ids[] = $row['id'];

                if (empty($subtask_parent_conditions[$row['type']])) {
                    $subtask_parent_conditions[$row['type']] = [];
                }

                $subtask_parent_conditions[$row['type']][] = $row['id'];
            }

            foreach ($subtask_parent_conditions as $k => $v) {
                $subtask_parent_conditions[$k] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $k, $v);
            }

            $subtask_parent_conditions = '(' . implode(' OR ', $subtask_parent_conditions) . ')';

            $this->cleanUpSubscriptions($subtask_parent_conditions);
            $this->cleanUpFavorites($subtask_parent_conditions);

            $this->execute("DELETE FROM $subtasks WHERE id IN (?)", $subtask_ids);
        }
    }

    /**
     * @param $parent_conditions
     */
    private function cleanUpSubscriptions($parent_conditions)
    {
        $this->execute('DELETE FROM ' . $this->useTables('subscriptions')[0] . " WHERE $parent_conditions");
    }

    /**
     * @param string $parent_conditions
     */
    private function cleanUpFavorites($parent_conditions)
    {
        $this->execute('DELETE FROM ' . $this->useTables('favorites')[0] . " WHERE $parent_conditions");
    }

    /**
     * @param string $parent_conditions
     */
    private function cleanUpComments($parent_conditions)
    {
        $comments = $this->useTables('comments')[0];

        if ($rows = $this->execute("SELECT id, type FROM $comments WHERE $parent_conditions")) {
            $comment_ids = [];
            $comment_parent_conditions = [];

            foreach ($rows as $row) {
                $comment_ids[] = $row['id'];

                if (empty($comment_parent_conditions[$row['type']])) {
                    $comment_parent_conditions[$row['type']] = [];
                }

                $comment_parent_conditions[$row['type']][] = $row['id'];
            }

            foreach ($comment_parent_conditions as $k => $v) {
                $comment_parent_conditions[$k] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $k, $v);
            }

            $this->cleanUpAttachments('(' . implode(' OR ', $comment_parent_conditions) . ')');
            $this->execute("DELETE FROM $comments WHERE id IN (?)", $comment_ids);
        }
    }

    /**
     * @param string $parent_conditions
     */
    private function cleanUpAttachments($parent_conditions)
    {
        $attachments = $this->useTables('attachments')[0];

        if ($rows = $this->execute("SELECT id, location FROM $attachments WHERE $parent_conditions")) {
            $attachment_ids = [];
            $to_unlink = [];

            foreach ($rows as $row) {
                $attachment_ids[] = $row['id'];

                $location = $row['location'] ? trim($row['location']) : '';

                if (empty($location)) {
                    continue;
                }

                $attachment_path = UPLOAD_PATH . '/' . $location;

                if (file_exists($attachment_path)) {
                    $to_unlink[] = $attachment_path;
                }
            }

            $this->execute("DELETE FROM $attachments WHERE id IN (?)", $attachment_ids);

            foreach ($to_unlink as $path) {
                @unlink($path);
            }
        }
    }

    /**
     * @param string $parent_conditions
     */
    private function cleanUpReminders($parent_conditions)
    {
        [$reminders, $reminder_users] = $this->useTables('reminders', 'reminder_users');

        if ($reminder_ids = $this->executeFirstColumn("SELECT id FROM $reminders WHERE $parent_conditions")) {
            $this->execute("DELETE FROM $reminders WHERE id IN (?)", $reminder_ids);
            $this->execute("DELETE FROM $reminder_users WHERE reminder_id IN (?)", $reminder_ids);
        }
    }
}
