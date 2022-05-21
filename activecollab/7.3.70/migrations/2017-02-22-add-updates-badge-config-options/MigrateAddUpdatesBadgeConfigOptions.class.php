<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddUpdatesBadgeConfigOptions extends AngieModelMigration
{
    public function up()
    {
      $this->addConfigOption('updates_show_notifications', true);
      $this->addConfigOption('updates_play_sound', false);
    }
}
