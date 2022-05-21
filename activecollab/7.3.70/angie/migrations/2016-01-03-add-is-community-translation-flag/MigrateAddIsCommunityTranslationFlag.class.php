<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsCommunityTranslationFlag extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('languages')->addColumn(
            new DBBoolColumn('is_community_translation'),
            'is_rtl'
        );
        $this->doneUsingTables();
    }
}
