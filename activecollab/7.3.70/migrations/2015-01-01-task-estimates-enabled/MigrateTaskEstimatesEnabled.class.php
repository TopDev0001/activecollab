<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce configuration option that lets users enable or disable task estimates.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskEstimatesEnabled extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('task_estimates_enabled', true);
    }
}
