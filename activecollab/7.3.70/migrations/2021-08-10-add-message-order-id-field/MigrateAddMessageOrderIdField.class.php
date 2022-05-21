<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddMessageOrderIdField extends AngieModelMigration
{
    public function up()
    {
        $table_name = 'messages';

        if ($this->tableExists($table_name)) {
            $table = $this->useTableForAlter($table_name);

            if (!$table->getColumn('order_id')) {
                $table->addColumn(
                    DBIntegerColumn::create(
                    'order_id',
                    DBColumn::NORMAL,
                    0
                    )->setUnsigned(true)->setSize(DBColumn::BIG),
                    'id'
                );
                $table->addIndex(DBIndex::create('order_id'));
            }

            $this->execute("UPDATE {$table_name} SET order_id = id");
        }

        $this->doneUsingTables();
    }
}
