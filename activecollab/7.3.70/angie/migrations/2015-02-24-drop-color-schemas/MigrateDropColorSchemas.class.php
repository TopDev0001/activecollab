<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop custom color schemas.
 *
 * @package angie.migrations
 */
class MigrateDropColorSchemas extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('current_scheme');
        $this->removeConfigOption('custom_schemes');
    }
}
