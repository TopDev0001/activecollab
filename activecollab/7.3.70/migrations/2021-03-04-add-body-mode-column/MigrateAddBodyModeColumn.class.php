<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddBodyModeColumn extends AngieModelMigration
{
    public function up()
    {
        $tables = [
            'tasks',
            'recurring_tasks',
            'notes',
            'discussions',
            'project_template_elements',
            'comments',
        ];

        foreach ($tables as $tableName) {
            if ($this->tableExists($tableName)) {
                $table = $this->useTableForAlter($tableName);

                if (!$table->getColumn('body_mode')) {
                    $table->addColumn(
                        new DBEnumColumn('body_mode', ['paragraph', 'break-line'], 'paragraph'),
                        'body'
                    );
                }
            }
        }

        $this->doneUsingTables();
    }
}
