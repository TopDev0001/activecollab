<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Clean up notebooks module.
 *
 * @package angie.migrations
 */
class MigrateUninstallNotebooksModule extends AngieModelMigration
{
    /**
     * Construct the migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateTextDocumentAndNotebookPermissions');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->isModuleInstalled('notebooks')) {
            $this->dropTable('notebook_pages');
            $this->dropTable('notebook_page_versions');
            $this->removeConfigOption('notebook_categories');
            $this->removeModule('notebooks');
        }
    }
}
