<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Initialize morning paper configuration options.
 *
 * @package ActiveCollab.migrations
 */
class MigrateInitMorningPaperOptions extends AngieModelMigration
{
    /**
     * Upgrade database.
     */
    public function up()
    {
        $this->addConfigOption('morning_paper_enabled', true);
        $this->addConfigOption('morning_paper_include_all_projects', false);
        $this->addConfigOption('morning_paper_last_activity', 0);
    }
}
