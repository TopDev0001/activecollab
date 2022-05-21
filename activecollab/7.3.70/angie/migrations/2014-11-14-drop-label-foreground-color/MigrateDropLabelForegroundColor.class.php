<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate drop label foreground color model.
 *
 * @package angie.migrations
 */
class MigrateDropLabelForegroundColor extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $labels = $this->useTableForAlter('labels');

        $labels->dropColumn('foreground_color');
        $labels->alterColumn('background_color', DBStringColumn::create('color', 50));
    }
}
