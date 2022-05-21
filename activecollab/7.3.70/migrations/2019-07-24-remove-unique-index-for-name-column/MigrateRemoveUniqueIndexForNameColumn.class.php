<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRemoveUniqueIndexForNameColumn extends AngieModelMigration
{
    public function up()
    {
        $table = $this->useTableForAlter('day_offs');

        if ($table->indexExists('name')) {
            $table->dropIndex('name');
        }

        $this->doneUsingTables();
    }
}
