<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateQuickbooksAsDefaultInvoicingAppConfigOption extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand() && $this->getConfigOptionValue('default_accounting_app') == 'quickbooks') {
            [$integrations_table] = $this->useTables('integrations');

            $raw_additional_properties = DB::executeFirstCell(
                "SELECT `raw_additional_properties` FROM `$integrations_table` WHERE `type` = ?",
                QuickbooksIntegration::class
            );

            $data = $raw_additional_properties ? unserialize($raw_additional_properties) : [];

            if (!array_key_exists('authorized_on', $data) || empty($data['authorized_on'])) {
                $this->setConfigOptionValue('default_accounting_app');
            }
        }
    }
}
