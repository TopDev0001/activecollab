<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateWebhooksToUseInstanceClassNameFromField extends AngieModelMigration
{
    public function up()
    {
        $migrations = $this->useTableForAlter('webhooks');

        if (!$migrations->getColumn('type')) {
            $migrations->addColumn(
                new DBTypeColumn('Webhook'),
                'id'
            );
        }

        $this->execute('UPDATE `webhooks` SET `type` = ?', 'Webhook');

        $this->doneUsingTables();
    }
}
