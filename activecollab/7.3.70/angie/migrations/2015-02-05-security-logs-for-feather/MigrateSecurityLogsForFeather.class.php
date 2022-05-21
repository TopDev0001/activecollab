<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update security logs for Feather.
 *
 * @package angie.migrations
 */
class MigrateSecurityLogsForFeather extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $security_logs = $this->useTableForAlter('security_logs');

        $this->execute('TRUNCATE TABLE ' . $security_logs->getName());
        $security_logs->dropColumn('is_api');

        $this->dropTable('api_token_logs');

        $this->doneUsingTables();
    }
}
