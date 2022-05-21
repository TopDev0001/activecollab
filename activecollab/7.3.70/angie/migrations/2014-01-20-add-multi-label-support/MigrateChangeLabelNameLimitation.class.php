<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Change label name limitation (move it from DB to model).
 *
 * @package angie.migrations
 */
class MigrateChangeLabelNameLimitation extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $labels_table = $this->useTableForAlter('labels');
        $labels_table->alterColumn('name', DBStringColumn::create('name', 30));
    }
}
