<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveOnLogoutUrl extends AngieModelMigration
{
    public function up()
    {
        $this->removeConfigOption('on_logout_url');
    }
}
