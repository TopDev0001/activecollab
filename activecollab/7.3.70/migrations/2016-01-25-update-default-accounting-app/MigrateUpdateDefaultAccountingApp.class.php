<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateDefaultAccountingApp extends AngieModelMigration
{
    public function up()
    {
        if ($this->getConfigOptionValue('default_accounting_app') == 'invoicing') {
            $this->setConfigOptionValue('default_accounting_app');
        }
    }
}
