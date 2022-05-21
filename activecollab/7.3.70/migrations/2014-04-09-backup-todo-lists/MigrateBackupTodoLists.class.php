<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Backup old to do lists and clean up supporting tables.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateBackupTodoLists extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $project_objects = $this->useTables('project_objects')[0];

        if ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $project_objects WHERE type = 'TodoList'")) {
            [$todo_lists, $subtasks, $todo_list_subtasks, $categories, $todo_list_categories, $reminders, $reminder_users] = $this->useTables('backup_todo_lists', 'subtasks', 'backup_todo_list_subtasks', 'categories', 'backup_todo_list_categories', 'reminders', 'reminder_users');

            $this->execute("CREATE TABLE $todo_lists LIKE $project_objects");
            $this->execute("INSERT $todo_lists SELECT * FROM $project_objects WHERE type = 'TodoList'");
            $this->execute("DELETE FROM $project_objects WHERE type = 'TodoList'");

            $this->execute("CREATE TABLE $todo_list_subtasks LIKE $subtasks");
            $this->execute("INSERT $todo_list_subtasks SELECT * FROM $subtasks WHERE parent_type = 'TodoList'");
            $this->execute("DELETE FROM $subtasks WHERE parent_type = 'TodoList'");

            $this->execute("CREATE TABLE $todo_list_categories LIKE $categories");
            $this->execute("INSERT $todo_list_categories SELECT * FROM $categories WHERE type = 'TodoListCategory'");
            $this->execute("DELETE FROM $categories WHERE type = 'TodoListCategory'");

            $subtask_ids = $this->executeFirstColumn("SELECT id FROM $todo_list_subtasks");

            foreach ($this->useTables('subscriptions', 'favorites') as $table) {
                $this->execute("DELETE FROM $table WHERE parent_type = 'TodoList'");

                if ($subtask_ids) {
                    $this->execute("DELETE FROM $table WHERE parent_type = 'ProjectObjectSubtask' AND parent_id IN (?)", $subtask_ids);
                }
            }

            if ($reminder_ids = $this->executeFirstColumn("SELECT id FROM $reminders WHERE parent_type = 'TodoList'")) {
                $this->execute("DELETE FROM $reminder_users WHERE reminder_id IN (?)", $reminder_ids);
                $this->execute("DELETE FROM $reminders WHERE id IN (?)", $reminder_ids);
            }
        }

        $this->removeModule('todo');

        $this->doneUsingTables();
    }
}
