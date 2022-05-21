<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateResetHelpImproveFlag extends AngieModelMigration
{
    public function up()
    {
        $this->setConfigOptionValue('help_improve_application', false);
    }
}
