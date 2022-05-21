<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Clean up file and document categories.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCleanUpFileAndDocumentCategories extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $categories = $this->useTables('categories')[0];

        $this->execute("DELETE FROM $categories WHERE type != 'ProjectCategory'");
        $this->execute("UPDATE $categories SET parent_type = '', parent_id = '0'");

        $this->doneUsingTables();
    }
}
