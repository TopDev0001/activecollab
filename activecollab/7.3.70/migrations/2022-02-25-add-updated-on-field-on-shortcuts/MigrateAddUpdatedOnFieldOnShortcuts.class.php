<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddUpdatedOnFieldOnShortcuts extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('shortcuts')) {
            $shortcuts = $this->useTableForAlter('shortcuts');

            if (!$shortcuts->getColumn('updated_on')) {
                $shortcuts->addColumn(
                    new DBUpdatedOnColumn(),
                    'position'
                );
            }

            $this->doneUsingTables();
        }
    }
}
