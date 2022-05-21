<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Temporary disable mail to project feature.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTemporaryDisableMailToProject extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('UPDATE projects SET mail_to_project_email = NULL');
    }
}
