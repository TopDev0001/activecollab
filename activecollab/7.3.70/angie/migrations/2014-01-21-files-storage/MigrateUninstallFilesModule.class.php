<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Uninstall files module when files are migrated to the new storage.
 *
 * @package angie.migrations
 */
class MigrateUninstallFilesModule extends AngieModelMigration
{
    /**
     * Construct the migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateFilesToSystem');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->isModuleInstalled('files')) {
            $this->removeConfigOption('asset_categories');
            $this->removeModule('files');
        }
    }
}
