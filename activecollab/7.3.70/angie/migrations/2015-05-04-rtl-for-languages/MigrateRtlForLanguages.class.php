<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRtlForLanguages extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('languages')->addColumn(
            new DBBoolColumn('is_rtl'),
            'thousands_separator'
        );
        $this->doneUsingTables();
    }
}
