<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddMessageChangedOnColumn extends AngieModelMigration
{
    public function up()
    {
        $table_name = 'messages';

        if ($this->tableExists($table_name)) {
            $table = $this->useTableForAlter($table_name);

            if (!$table->getColumn('changed_on')) {
                $table->addColumn(
                    new DBDateTimeColumn('changed_on'),
                    'body'
                );
            }
        }

        $this->doneUsingTables();
    }
}
