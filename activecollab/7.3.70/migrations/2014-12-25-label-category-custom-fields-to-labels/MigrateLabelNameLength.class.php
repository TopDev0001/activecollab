<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateLabelNameLength extends AngieModelMigration
{
    public function up()
    {
        $labels = $this->useTableForAlter('labels');

        $this->execute('ALTER TABLE ' . $labels->getName() . ' CHARACTER SET = utf8mb4');
        $labels->alterColumn(
            'name',
            new DBNameColumn(191, true, 'type')
        );

        $this->doneUsingTables();
    }
}
