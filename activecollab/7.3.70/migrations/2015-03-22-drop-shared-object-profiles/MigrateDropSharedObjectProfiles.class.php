<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop shared object profiles model.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropSharedObjectProfiles extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('shared_object_profiles');
    }
}
