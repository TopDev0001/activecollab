<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddShowQuickbooksOauth2MigrationPointerConfigOption extends AngieModelMigration
{
    public function up()
    {
        $config_option = 'show_quickbooks_oauth2_migration_pointer';

        if (!$this->getConfigOptionValue($config_option)) {
            $this->addConfigOption($config_option, false);
        }

        if (!AngieApplication::isOnDemand()) {
            [$integrations_table] = $this->useTables('integrations');

            $raw_additional_properties = DB::executeFirstCell(
                "SELECT `raw_additional_properties` FROM `$integrations_table` WHERE `type` = ?",
                QuickbooksIntegration::class
            );

            $data = $raw_additional_properties ? unserialize($raw_additional_properties) : [];

            if ($data['access_token'] && $data['access_token_secret']) {
                /** @var Owner[] $users */
                $owners = Users::findOwners();

                if ($owners) {
                    foreach ($owners as $owner) {
                        ConfigOptions::setValueFor($config_option, $owner, true);
                    }
                }
            }

            $this->doneUsingTables();
        }
    }
}
