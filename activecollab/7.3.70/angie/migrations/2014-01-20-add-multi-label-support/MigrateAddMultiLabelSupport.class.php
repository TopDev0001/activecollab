<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add multi-label support to the system.
 *
 * @package angie.migrations
 */
class MigrateAddMultiLabelSupport extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->createTable(DB::createTable('parents_labels')->addColumns([
            new DBParentColumn(false, false),
            DBFkColumn::create('label_id'),
        ])->addIndices([
            DBIndex::create('parents_label', DBIndex::PRIMARY, ['parent_type', 'parent_id', 'label_id']),
            DBIndex::create('label_id'),
        ]));
    }
}
