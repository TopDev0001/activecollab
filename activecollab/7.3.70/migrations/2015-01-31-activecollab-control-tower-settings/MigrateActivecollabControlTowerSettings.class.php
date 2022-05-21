<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate ActiveCollab control tower settings.
 *
 * @package ActiveCollab.migrations
 */
class MigrateActivecollabControlTowerSettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('control_tower_check_for_new_version');
    }
}
