<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop interface from user sessions table.
 *
 * @package angie.migrations
 */
class MigrateDropInterfaceFromUserSessions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->useTableForAlter('user_sessions')->dropColumn('interface');
        $this->doneUsingTables();
    }
}
