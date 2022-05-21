<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove all instances where system notifications home screen widget was used.
 *
 * @package angie.migrations
 */
class MigrateRemoveSystemNotificationsHomescreenWidget extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('DELETE FROM homescreen_widgets WHERE type = ?', 'SystemNotificationsHomescreenWidget');
    }
}
