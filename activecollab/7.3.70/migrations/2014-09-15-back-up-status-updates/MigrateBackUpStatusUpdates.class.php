<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Back up status updates to status updates project.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateBackUpStatusUpdates extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('status_updates')) {
            $status_updates = $this->useTables('status_updates')[0];

            if ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $status_updates")) {
                [$discussions, $comments, $projects, $project_users] = $this->useTables('discussions', 'comments', 'projects', 'project_users');
                [$owner_id, $owner_name, $owner_email] = $this->getFirstUsableOwner();

                $escaped_owner_id = DB::escape($owner_id);

                if ($this->execute("INSERT INTO $projects (slug, mail_to_project_code, company_id, name, leader_id, leader_name, leader_email, created_on, created_by_id, created_by_name, created_by_email, updated_on) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?, ?, UTC_TIMESTAMP())", $this->getSlug($projects), $this->getMailToProjectCode($projects), $this->getOwnerCompanyId(), 'Status Updates Backup', $owner_id, $owner_name, $owner_email, $owner_id, $owner_name, $owner_email)) {
                    $project_id = $this->lastInsertId();

                    $escaped_project_id = DB::escape($project_id);

                    $this->execute("INSERT INTO $project_users (project_id, user_id) VALUES ($escaped_project_id, $escaped_owner_id)");

                    if ($rows = $this->execute("SELECT * FROM $status_updates WHERE parent_id IS NULL OR parent_id = '0'")) {
                        $comments_batch = new DBBatchInsert($comments, ['parent_type', 'parent_id', 'body', 'created_on', 'created_by_id', 'created_by_name', 'created_by_email', 'updated_on']);

                        foreach ($rows as $row) {
                            if (strlen_utf($row['message']) > 60) {
                                $name = substr_utf($row['message'], 0, 60) . '…';
                            } else {
                                $name = $row['message'];
                            }

                            $this->execute("INSERT INTO $discussions (project_id, name, body, created_on, created_by_id, created_by_name, created_by_email, updated_on) VALUES ($escaped_project_id, ?, ?, ?, ?, ?, ?, ?)", $name, $row['message'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['created_on']);

                            if ($reply_rows = $this->execute("SELECT * FROM $status_updates WHERE parent_id = ?", $row['id'])) {
                                $discussion_id = $this->lastInsertId();

                                foreach ($reply_rows as $reply_row) {
                                    $comments_batch->insert('Discussion', $discussion_id, $reply_row['message'], $reply_row['created_on'], $reply_row['created_by_id'], $reply_row['created_by_name'], $reply_row['created_by_email'], $reply_row['created_on']);
                                }
                            }
                        }

                        $comments_batch->done();
                    }
                }
            }

            $this->doneUsingTables();
        }

        $this->dropTable('status_updates');
    }

    /**
     * @param  string $projects_table
     * @return string
     */
    private function getSlug($projects_table)
    {
        $slug = 'status-updates-backup';
        $counter = 1;

        while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $projects_table WHERE slug = ?", $slug)) {
            $slug = 'status-updates-backup-' . $counter++;
        }

        return $slug;
    }

    /**
     * @param  string $projects_table
     * @return string
     */
    private function getMailToProjectCode($projects_table)
    {
        do {
            $mail_to_project_code = make_string(7);
        } while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $projects_table WHERE mail_to_project_code = ?", $mail_to_project_code));

        return $mail_to_project_code;
    }

    /**
     * @return int
     */
    private function getOwnerCompanyId()
    {
        return $this->executeFirstCell('SELECT id FROM ' . $this->useTables('companies')[0] . ' WHERE is_owner = ? ORDER BY id DESC LIMIT 0, 1', true);
    }
}
