<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIntegrationsTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('integrations')) {
            return;
        }

        $this->createTable(
            DB::createTable('integrations')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn(),
                    new DBAdditionalPropertiesColumn(),
                    new DBCreatedOnByColumn(),
                ]
            )
        );
    }
}
