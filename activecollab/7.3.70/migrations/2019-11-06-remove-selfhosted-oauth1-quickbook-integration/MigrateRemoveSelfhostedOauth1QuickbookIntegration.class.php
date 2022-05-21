<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRemoveSelfhostedOauth1QuickbookIntegration extends AngieModelMigration
{
    /**
     * Execute after.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateAddShowQuickbooksOauth2MigrationPointerConfigOption');
    }

    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            [$integrations_table] = $this->useTables('integrations');
            DB::execute("DELETE FROM `$integrations_table` WHERE `type` = ?", QuickbooksIntegration::class);
            $this->doneUsingTables();
        }
    }
}
