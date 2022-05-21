<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddFilterAssigneesToWebhooks extends AngieModelMigration
{
    public function up()
    {
        $webhooks = $this->useTableForAlter('webhooks');

        if (!$webhooks->getColumn('filter_assignees')) {
            $webhooks->addColumn(
                new DBTextColumn('filter_assignees'),
                'filter_projects'
            );
        }
    }
}
