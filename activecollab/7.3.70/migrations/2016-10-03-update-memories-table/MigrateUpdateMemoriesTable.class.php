<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateMemoriesTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('memories')) {
            $memories = $this->useTableForAlter('memories');
            $memories->alterColumn(
                'value',
                (new DBTextColumn('value'))
                    ->setSize(DBTextColumn::MEDIUM)
            );
            $this->doneUsingTables();
        }
    }
}
