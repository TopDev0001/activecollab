<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddFeaturePointersModels extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('feature_pointers')) {
            $this->createTable(
                DB::createTable('feature_pointers')->addColumns([
                    new DBIdColumn(),
                    new DBTypeColumn('FeaturePointer'),
                    DBIntegerColumn::create('parent_id', DBColumn::NORMAL)->setUnsigned(true),
                    new DBCreatedOnColumn(),
                ])->addIndices([
                    DBIndex::create('parent_id'),
                ])
            );
        }

        if (!$this->tableExists('feature_pointer_dismissals')) {
            $this->createTable(
                DB::createTable('feature_pointer_dismissals')->addColumns([
                    DBIntegerColumn::create('feature_pointer_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                ])->addIndices([
                    new DBIndexPrimary(['feature_pointer_id', 'user_id']),
                    DBIndex::create('user_id'),
                ])
            );
        }
    }
}
