<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Rename priority field to is_important.
 *
 * @package ActiveCollab.migrations
 */
class MigrateIdentityLogoConfigOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('identity_logo');
    }
}
