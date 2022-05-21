<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveUniqueKeyFromFileLocation extends AngieModelMigration
{
    public function up()
    {
        foreach (['attachments', 'files', 'uploaded_files'] as $table_name) {
            $table = $this->useTableForAlter($table_name);

            if ($table->indexExists('location')) {
                $table->alterIndex('location', DBIndex::create('location'));
            }
        }

        $this->doneUsingTables();
    }
}
