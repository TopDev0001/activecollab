<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Class description.
 *
 * @package angie.migrations
 */
class MigrateDropPasswordAutoExpireSetting extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('password_policy_auto_expire');
    }
}
