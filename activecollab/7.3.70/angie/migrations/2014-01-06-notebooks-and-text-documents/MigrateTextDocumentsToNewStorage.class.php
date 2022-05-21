<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate text documents to new storage.
 *
 * @package angie.migrations
 */
class MigrateTextDocumentsToNewStorage extends AngieModelMigration
{
    /**
     * Construct the migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateInitTextDocumentsFramework');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->isModuleInstalled('files')) {
            [$project_objects, $attachments, $subscriptions, $comments, $reminders] = $this->useTables('project_objects', 'attachments', 'subscriptions', 'comments', 'reminders');

            if ($rows = $this->execute("SELECT id, project_id, name, body, state, original_state, visibility, original_visibility, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, integer_field_1 AS 'version_num', datetime_field_1 AS 'last_version_on', integer_field_2 AS 'last_version_by_id', varchar_field_1 AS 'last_version_by_name', varchar_field_2 AS 'last_version_by_email' FROM $project_objects WHERE type = ?", 'TextDocument')) {
                $project_positions = [];

                foreach ($rows as $row) {
                    $project_id = $row['project_id'];

                    if (isset($project_positions[$project_id])) {
                        ++$project_positions[$project_id];
                    } else {
                        $project_positions[$project_id] = 1;
                    }

                    // Fix confidential visibility
                    foreach (['state', 'original_state', 'visibility', 'original_visibility'] as $k) {
                        if ($row[$k] === null) {
                            continue;
                        }

                        if ($row[$k] < 0) {
                            $row[$k] = 0;
                        }
                    }

                    $this->execute("INSERT INTO text_documents (id, type, parent_type, parent_id, name, body, state, original_state, visibility, original_visibility, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, last_version_on, last_version_by_id, last_version_by_name, last_version_by_email, version, position) VALUES (?, 'ProjectTextDocument', 'Project', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $row['id'], $project_id, $row['name'], $row['body'], $row['state'], $row['original_state'], $row['visibility'], $row['original_visibility'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['updated_on'], $row['updated_by_id'], $row['updated_by_name'], $row['updated_by_email'], $row['last_version_on'], $row['last_version_by_id'], $row['last_version_by_name'], $row['last_version_by_email'], $row['version_num'], $project_positions[$project_id]);
                }

                $this->execute("UPDATE $attachments SET parent_type = 'ProjectTextDocument' WHERE parent_type = 'TextDocument'");
                $this->execute("UPDATE $subscriptions SET parent_type = 'ProjectTextDocument' WHERE parent_type = 'TextDocument'");
                $this->execute("UPDATE $comments SET type = 'TextDocumentComment', parent_type = 'ProjectTextDocument' WHERE parent_type = 'TextDocument'");
                $this->execute("UPDATE $reminders SET parent_type = 'ProjectTextDocument' WHERE parent_type = 'TextDocument'");

                $this->execute("DELETE FROM $project_objects WHERE type = 'TextDocument'");
            }

            $this->doneUsingTables();
        }
    }
}
