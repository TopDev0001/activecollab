<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add config option wallpaper.
 *
 * @package angie.migrations
 */
class MigrateAddWallpaperConfigOptions extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $this->addConfigOption('wallpaper', 'wallpaper.jpg');
    }
}
