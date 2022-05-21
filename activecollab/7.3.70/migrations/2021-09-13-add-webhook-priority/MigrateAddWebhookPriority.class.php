<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddWebhookPriority extends AngieModelMigration
{
    public function up()
    {
        $webhooks = $this->useTableForAlter('webhooks');

        if ($webhooks->getColumn('priority')) {
            return;
        }

        $webhooks->addColumn(
            new DBEnumColumn(
                'priority',
                [
                    'low',
                    'normal',
                    'high',
                ],
                'normal'
            )
        );
    }
}
