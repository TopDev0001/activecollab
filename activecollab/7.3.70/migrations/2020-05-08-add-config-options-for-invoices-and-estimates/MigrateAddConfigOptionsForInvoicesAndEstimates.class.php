<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddConfigOptionsForInvoicesAndEstimates extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('invoices_enabled')) {
            $this->addConfigOption('invoices_enabled', true);
        }

        if (!ConfigOptions::exists('invoices_enabled_lock')) {
            $this->addConfigOption('invoices_enabled_lock', true);
        }

        if (!ConfigOptions::exists('estimates_enabled')) {
            $this->addConfigOption('estimates_enabled', true);
        }

        if (!ConfigOptions::exists('estimates_enabled_lock')) {
            $this->addConfigOption('estimates_enabled_lock', true);
        }
    }
}
