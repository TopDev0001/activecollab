<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add my work collapsed sections configuration option.
 *
 * @package ActiveCollab.migrations
 */
class MigrateMyWorkCollapsedSections extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('my_work_collapsed_sections');
    }
}
