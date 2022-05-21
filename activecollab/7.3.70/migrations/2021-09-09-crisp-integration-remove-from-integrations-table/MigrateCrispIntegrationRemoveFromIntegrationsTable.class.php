<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateCrispIntegrationRemoveFromIntegrationsTable extends AngieModelMigration
{
    public function up()
    {
        [$integrations_table] = $this->useTables('integrations');
        $this->execute("DELETE FROM `$integrations_table` WHERE type = 'CrispIntegration'");
        $this->doneUsingTables();
    }
}
