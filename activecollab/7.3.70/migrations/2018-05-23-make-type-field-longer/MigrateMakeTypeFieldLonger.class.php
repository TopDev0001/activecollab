<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateMakeTypeFieldLonger extends AngieModelMigration
{
    public function up()
    {
        foreach (DB::listTables() as $table_name) {
            $table = $this->useTableForAlter($table_name);

            $column = $table->getColumn('type');

            if ($column instanceof DBStringColumn && $column->getLength() == 50) {
                $table->alterColumn($column->getName(), $column->setLength(191));
            }
        }
    }
}
