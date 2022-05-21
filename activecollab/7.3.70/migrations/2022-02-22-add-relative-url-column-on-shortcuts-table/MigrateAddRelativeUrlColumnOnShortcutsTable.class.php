<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddRelativeUrlColumnOnShortcutsTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('shortcuts')) {
            $shortcuts = $this->useTableForAlter('shortcuts');

            if (!$shortcuts->getColumn('relative_url')) {
                $shortcuts->addColumn(
                    (new DBTextColumn('relative_url'))->setDefault(null),
                    'url'
                );
            }

            $this->doneUsingTables();
        }
    }
}
