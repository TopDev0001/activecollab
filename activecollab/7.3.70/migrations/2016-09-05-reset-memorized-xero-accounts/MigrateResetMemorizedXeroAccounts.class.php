<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateResetMemorizedXeroAccounts extends AngieModelMigration
{
    public function up()
    {
        AngieApplication::memories()->forget('xero_accounts');
    }
}
