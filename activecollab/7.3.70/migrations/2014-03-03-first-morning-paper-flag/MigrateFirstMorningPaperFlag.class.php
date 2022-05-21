<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Set up first morning paper flag.
 *
 * @package ActiveCollab.migrations
 */
class MigrateFirstMorningPaperFlag extends AngieModelMigration
{
    /**
     * Upgrade database.
     */
    public function up()
    {
        $this->addConfigOption('first_morning_paper', true);
    }
}
