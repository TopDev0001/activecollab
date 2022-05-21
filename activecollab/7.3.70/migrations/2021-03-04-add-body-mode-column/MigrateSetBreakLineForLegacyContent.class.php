<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateSetBreakLineForLegacyContent extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateAddBodyModeColumn');
    }

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

        foreach ($tables as $table) {
            $this->execute(
                sprintf(
                    'UPDATE `%s` SET `body_mode` = ?, `updated_on` = UTC_TIMESTAMP() WHERE `body` LIKE ?',
                    $table
                ),
                'break-line',
                '%<br%'
            );
        }
    }
}
