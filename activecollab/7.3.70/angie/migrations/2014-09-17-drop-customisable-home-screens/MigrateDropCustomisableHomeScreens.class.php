<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop customisable home screens.
 *
 * @package angie.migrations
 */
class MigrateDropCustomisableHomeScreens extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('homescreens', 'homescreen_tabs', 'homescreen_widgets');
        $this->removeConfigOption('default_homescreen_tab_id');
    }
}
