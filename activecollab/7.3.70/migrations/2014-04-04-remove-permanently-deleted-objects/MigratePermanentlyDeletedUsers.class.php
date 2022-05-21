<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate permanently deleted users.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigratePermanentlyDeletedUsers extends AngieModelMigration
{
    /**
     * Construct a new instance.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePermanentlyDeletedCompanies');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        defined('STATE_DELETED') or define('STATE_DELETED', 0);

        [$users] = $this->useTables('users');

        $users_to_delete = $this->getUsersToDelete();

        if (count($users_to_delete)) {
            $escaped_user_ids = DB::escape(array_keys($users_to_delete)); // This will be used several times

            $this->updateTimeRecordsAndExpenses($users_to_delete); // Reset user_id field for tracked time and expense records
            $this->updateNotifications($users_to_delete, $escaped_user_ids); // Remove all notifications about deleted users, as well as records where deleted users are recipients
            $this->cleanUpAnnouncements($escaped_user_ids); // Clean up announcement, announcement targets and dismissals
            $this->cleanUpAssignments($escaped_user_ids); // Clean up task and subtask assignments
            $this->updateOpenProjectRequest($escaped_user_ids); // Un-take open project requests if they are taken by deleted users
            $this->dropConfigOptionValues($escaped_user_ids); // Drop user specific configuration option values
            $this->dropProjectUsers($escaped_user_ids); // Update project leader and project involvment
            $this->dropUserHomescreenTabs($escaped_user_ids); // Drop home screen tabs and widgets
            $this->dropUserRelations($escaped_user_ids); // Drop various user relations (subscriptions, reminders, favorites etc)
            $this->discoverAndUpdateTables($users_to_delete); // Update all _by_id tables and properly update field values

            $this->execute("DELETE FROM $users WHERE id IN ($escaped_user_ids)");
        }

        $this->doneUsingTables();
    }

    // ---------------------------------------------------
    //  Subroutines
    // ---------------------------------------------------

    /**
     * Return array of users that need to be deleted.
     *
     * @return array
     */
    private function getUsersToDelete()
    {
        [$users, $companies] = $this->useTables('users', 'companies');

        $company_ids = $this->executeFirstColumn("SELECT id FROM $companies");

        if ($company_ids) {
            $rows = $this->execute("SELECT id, first_name, last_name, email FROM $users WHERE state = ? OR company_id NOT IN (?)", STATE_DELETED, $company_ids);
        } else {
            $rows = $this->execute("SELECT id, first_name, last_name, email FROM $users WHERE state = ?", STATE_DELETED, $company_ids);
        }

        $users_to_delete = [];

        if ($rows) {
            foreach ($rows as $row) {
                $users_to_delete[(int) $row['id']] = ['name' => $this->getUserDisplayName($row), 'email' => $row['email']];
            }
        }

        return $users_to_delete;
    }

    /**
     * Return user display name.
     *
     * @param  array  $row
     * @return string
     */
    private function getUserDisplayName($row)
    {
        $first_name = $row['first_name'] ? trim($row['first_name']) : null;
        $last_name = $row['last_name'] ? trim($row['last_name']) : null;
        $email = $row['email'] ? trim($row['email']) : null;

        if ($first_name && $last_name) {
            return $first_name . ' ' . $last_name;
        } elseif ($first_name) {
            return $first_name;
        } elseif ($last_name) {
            return $last_name;
        } else {
            return substr($email, 0, strpos($email, '@'));
        }
    }

    /**
     * Drop connection between deleted users and tracked time records and expenses.
     *
     * @param array $users_to_delete
     */
    private function updateTimeRecordsAndExpenses($users_to_delete)
    {
        if ($this->tableExists('time_records') && $this->tableExists('expenses')) {
            [$time_records, $expenses] = $this->useTables('time_records', 'expenses');

            foreach ($users_to_delete as $user_id => $user_details) {
                $escaped_user_id = DB::escape($user_id);
                $escaped_user_name = DB::escape($user_details['name']);
                $escaped_user_email = DB::escape($user_details['email']);

                $this->execute("UPDATE $time_records SET user_id = NULL, user_name = $escaped_user_name, user_email = $escaped_user_email WHERE user_id = $escaped_user_id");
                $this->execute("UPDATE $expenses SET user_id = NULL, user_name = $escaped_user_name, user_email = $escaped_user_email WHERE user_id = $escaped_user_id");
            }
        }
    }

    /**
     * Update notifications.
     *
     * @param array  $users_to_delete
     * @param string $escaped_user_ids
     */
    private function updateNotifications($users_to_delete, $escaped_user_ids)
    {
        [$notifications, $notification_recipients] = $this->useTables('notifications', 'notification_recipients');

        // Drop notifications about users that are being removed
        if ($notification_ids = $this->executeFirstColumn("SELECT id FROM $notifications WHERE parent_type IN ('Administrator', 'Manager', 'Member', 'Subcontractor', 'Client', 'User') AND parent_id IN ($escaped_user_ids)")) {
            $this->execute("DELETE FROM $notification_recipients WHERE notification_id IN (?)", $notification_ids);
            $this->execute("DELETE FROM $notifications WHERE id IN (?)", $notification_ids);
        }

        // Drop notifications where deleted user is recipient
        $this->execute("DELETE FROM $notification_recipients WHERE recipient_id IN ($escaped_user_ids)");

        // Update notifications where deleted users are senders
        foreach ($users_to_delete as $user_id => $user_details) {
            $this->execute("UPDATE $notifications SET sender_id = NULL, sender_name = ?, sender_email = ? WHERE sender_id = ?", $user_details['name'], $user_details['email'], $user_id);
        }
    }

    /**
     * Clean up announcements.
     *
     * @param string $escaped_user_ids
     */
    private function cleanUpAnnouncements($escaped_user_ids)
    {
        [$announcements, $announcement_targets, $announcement_dismissals] = $this->useTables('announcements', 'announcement_target_ids', 'announcement_dismissals');

        if ($announcements_targeting_users = $this->executeFirstColumn("SELECT id FROM $announcements WHERE target_type = 'user'")) {
            $this->execute("DELETE FROM $announcement_targets WHERE announcement_id IN (?) AND target_id IN ($escaped_user_ids)", $announcements_targeting_users);
        }

        $this->execute("DELETE FROM $announcement_dismissals WHERE user_id IN ($escaped_user_ids)");
    }

    /**
     * Clean assignments.
     *
     * @param string $escaped_user_ids
     */
    private function cleanUpAssignments($escaped_user_ids)
    {
        [$project_objects, $subtasks, $assignments] = $this->useTables('project_objects', 'subtasks', 'assignments');

        if ($task_ids = $this->executeFirstColumn("SELECT id FROM $project_objects WHERE type = 'Task' AND assignee_id IN ($escaped_user_ids)")) {
            $this->execute("DELETE FROM $assignments WHERE parent_type = 'Task' AND parent_id IN (?)", $task_ids);
            $this->execute("UPDATE $project_objects SET assignee_id = NULL WHERE id IN (?)", $task_ids);
        }

        $this->execute("UPDATE $subtasks SET assignee_id = NULL WHERE assignee_id IN ($escaped_user_ids)");
    }

    /**
     * Un-take open project requests if they are taken by deleted users.
     *
     * @param string $escaped_user_ids
     */
    private function updateOpenProjectRequest($escaped_user_ids)
    {
        $project_requests = $this->useTables('project_requests')[0];

        $this->execute("UPDATE $project_requests SET taken_by_id = NULL, taken_by_name = NULL, taken_by_email = NULL WHERE closed_on IS NULL AND taken_by_id IN ($escaped_user_ids)");
    }

    /**
     * Drop user configration options.
     *
     * @param string $escaped_user_ids
     */
    private function dropConfigOptionValues($escaped_user_ids)
    {
        $config_option_values = $this->useTables('config_option_values')[0];

        $this->execute("DELETE FROM $config_option_values WHERE parent_type IN ('Administrator', 'Manager', 'Member', 'Subcontractor', 'Client', 'User') AND parent_id IN ($escaped_user_ids)");
    }

    /**
     * Drop project users.
     *
     * @param string $escaped_user_ids
     */
    private function dropProjectUsers($escaped_user_ids)
    {
        [$projects, $project_users] = $this->useTables('projects', 'project_users');

        [$default_leader_id, $default_leader_name, $default_leader_email] = $this->getFirstUsableOwner();

        if ($projects_lead_by_deleted_users = $this->executeFirstColumn("SELECT id FROM $projects WHERE leader_id IN ($escaped_user_ids)")) {
            $this->execute("UPDATE $projects SET leader_id = ?, leader_name = ?, leader_email = ? WHERE id IN (?)", $default_leader_id, $default_leader_name, $default_leader_email, $projects_lead_by_deleted_users);

            foreach ($projects_lead_by_deleted_users as $project_id) {
                $this->execute("REPLACE INTO $project_users (project_id, user_id) VALUES (?, ?)", $project_id, $default_leader_id);
            }
        }

        $this->execute("DELETE FROM $project_users WHERE user_id IN ($escaped_user_ids)"); // Drop project users relations
    }

    /**
     * Drop user homescreen tabs.
     *
     * @param string $escaped_user_ids
     */
    private function dropUserHomescreenTabs($escaped_user_ids)
    {
        [$tabs, $widgets] = $this->useTables('homescreen_tabs', 'homescreen_widgets');

        if ($tab_ids = $this->executeFirstColumn("SELECT id FROM $tabs WHERE user_id IN ($escaped_user_ids)")) {
            $this->execute("DELETE FROM $widgets WHERE homescreen_tab_id IN (?)", $tab_ids);
            $this->execute("DELETE FROM $tabs WHERE id IN (?)", $tab_ids);
        }
    }

    // ---------------------------------------------------
    //  Utility
    // ---------------------------------------------------

    /**
     * Drop user relations.
     *
     * @param string $escaped_user_ids
     */
    private function dropUserRelations($escaped_user_ids)
    {
        foreach ($this->useTables('api_client_subscriptions', 'user_sessions', 'calendar_users', 'favorites', 'reminder_users', 'security_logs', 'source_users', 'subscriptions', 'user_addresses') as $table) {
            if ($this->tableExists($table)) {
                $this->execute("DELETE FROM $table WHERE user_id IN ($escaped_user_ids)");
            }
        }
    }

    /**
     * Discover user relation fields and ID, name and email values before user accounts get permanently dropped.
     *
     * @param array $users_to_delete
     */
    private function discoverAndUpdateTables($users_to_delete)
    {
        $tables_with_parent_type = [];
        $update_parents = [];
        $update_by_ids = [];
        $update_fieldset = [];

        foreach (DB::listTables() as $table) {
            $table_fields = DB::listTableFields($table);

            foreach ($table_fields as $field) {
                if ($field == 'parent_type' && in_array('parent_id', $table_fields)) {
                    $tables_with_parent_type[] = $table;

                    if ((int) $this->executeFirstCell("SELECT COUNT(*) FROM $table WHERE parent_type IN ('Administrator', 'Manager', 'Member', 'Subcontractor', 'Client', 'User')") > 0) {
                        $update_parents[] = $table;
                    }
                } elseif (str_ends_with($field, '_by_id')) {
                    $name = substr($field, 0, strlen($field) - 6);

                    if (in_array("{$name}_by_name", $table_fields) && in_array("{$name}_by_email", $table_fields)) {
                        if (empty($update_fieldset[$table])) {
                            $update_fieldset[$table] = [];
                        }

                        $update_fieldset[$table][] = $name;
                    } else {
                        if (empty($update_by_ids[$table])) {
                            $update_by_ids[$table] = [];
                        }

                        $update_by_ids[$table][] = $field;
                    }
                }
            }
        }

        foreach ($users_to_delete as $user_id => $user_details) {
            $escaped_user_id = DB::escape($user_id);
            $escaped_user_name = DB::escape($user_details['name']);
            $escaped_user_email = DB::escape($user_details['email']);

            foreach ($update_by_ids as $table => $fields) {
                foreach ($fields as $field) {
                    try {
                        $this->execute("UPDATE $table SET $field = NULL WHERE $field = $escaped_user_id"); // In case we can have NULL, set NULL
                    } catch (DBQueryError $e) {
                        $this->execute("UPDATE $table SET $field = '0' WHERE $field = $escaped_user_id"); // On error, set 0
                    }
                }
            }

            foreach ($update_fieldset as $table => $fields) {
                foreach ($fields as $field) {
                    try {
                        $this->execute("UPDATE $table SET {$field}_by_id = NULL, {$field}_by_name = $escaped_user_name, {$field}_by_email = $escaped_user_email WHERE {$field}_by_id = $escaped_user_id"); // In case we can have NULL, set NULL
                    } catch (DBQueryError $e) {
                        $this->execute("UPDATE $table SET {$field}_by_id = '0', {$field}_by_name = $escaped_user_name, {$field}_by_email = $escaped_user_email WHERE {$field}_by_id = $escaped_user_id"); // In case we can't set NULL, set 0
                    }
                }
            }
        }
    }
}
