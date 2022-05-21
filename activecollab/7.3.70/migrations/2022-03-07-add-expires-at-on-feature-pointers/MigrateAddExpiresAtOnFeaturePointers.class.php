<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddExpiresAtOnFeaturePointers extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('feature_pointers')) {
            return;
        }

        $feature_table = $this->useTableForAlter('feature_pointers');

        if ($feature_table->getColumn('expires_on')) {
            return;
        }

        $feature_table->addColumn(new DBDateColumn('expires_on'), 'parent_id');
        $this->doneUsingTables();
    }
}
