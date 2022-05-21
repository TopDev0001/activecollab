<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop custom project fields and custom_fields table.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropCustomProjectFields extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        for ($i = 1; $i <= 10; ++$i) {
            if ($projects->getColumn("custom_field_{$i}")) {
                $projects->dropColumn("custom_field_{$i}");
            }
        }

        $this->dropTable('custom_fields');

        $this->doneUsingTables();
    }
}
