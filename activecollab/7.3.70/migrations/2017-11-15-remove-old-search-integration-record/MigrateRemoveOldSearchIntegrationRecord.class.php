<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveOldSearchIntegrationRecord extends AngieModelMigration
{
    public function up()
    {
        $this->execute('DELETE FROM integrations WHERE type = ?', SearchIntegration::class);
    }
}
