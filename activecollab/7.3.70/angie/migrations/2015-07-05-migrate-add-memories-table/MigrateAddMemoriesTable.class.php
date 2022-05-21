<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddMemoriesTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('memories')) {
            return;
        }

        $this->createTable(
            DB::createTable('memories')->addColumns(
                [
                    new DBIdColumn(),
                    DBStringColumn::create('key', 191, ''),
                    new DBTextColumn('value'),
                    new DBUpdatedOnColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('key', DBIndex::UNIQUE),
                ]
            )
        );
    }
}
