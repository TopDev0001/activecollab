<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Upgrade text document and notebook permissions.
 *
 * @package angie.migrations
 */
class MigrateTextDocumentAndNotebookPermissions extends AngieModelMigration
{
    /**
     * Construct the migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateNotebooksToNewStorage', 'MigrateTextDocumentsToNewStorage');
    }

    /**
     * Upgrade text document permissions.
     */
    public function up()
    {
        if ($this->isModuleInstalled('files') || $this->isModuleInstalled('notebooks')) {
            $rows = $this->execute('SELECT id, permissions FROM project_roles');

            if ($rows) {
                foreach ($rows as $row) {
                    $this->execute('UPDATE project_roles SET permissions = ? WHERE id = ?', serialize($this->setTextDocumentsPermissionValue($row['permissions'] ? unserialize($row['permissions']) : [])), $row['id']);
                }
            }

            $rows = $this->execute('SELECT user_id, project_id, permissions FROM project_users WHERE role_id IS NULL OR role_id = ?', 0);
            if ($rows) {
                foreach ($rows as $row) {
                    $this->execute('UPDATE project_users SET permissions = ? WHERE user_id = ? AND project_id = ?', serialize($this->setTextDocumentsPermissionValue($row['permissions'] ? unserialize($row['permissions']) : [])), $row['user_id'], $row['project_id']);
                }
            }
        }
    }

    /**
     * Set text documents permission based on existing permissions.
     *
     * @param  array $permissions
     * @return array
     */
    private function setTextDocumentsPermissionValue($permissions)
    {
        if (isset($permissions['file']) && isset($permissions['notebook'])) {
            $permissions['text_document'] = min((int) $permissions['file'], (int) $permissions['notebook']); // Use lowest of the two available permissions
            unset($permissions['notebook']);
        } elseif (isset($permissions['file'])) {
            $permissions['text_document'] = (int) $permissions['file'];
        } elseif (isset($permissions['notebook'])) {
            $permissions['text_document'] = (int) $permissions['notebook'];
            unset($permissions['notebook']);
        }

        return $permissions;
    }
}
