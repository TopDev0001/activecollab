<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add default accounting app config option.
 *
 * @package activeCollab.modules.system
 */
class MigrateAddDefaultAccountingAppConfigOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('default_accounting_app');
    }
}
