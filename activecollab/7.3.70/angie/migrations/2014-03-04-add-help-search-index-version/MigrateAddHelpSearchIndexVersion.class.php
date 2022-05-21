<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add help search index version.
 *
 * @package angie.migrations
 */
class MigrateAddHelpSearchIndexVersion extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('help_search_index_version');
    }
}
