<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRenameClientPlusMySubscriptionsConfigOption extends AngieModelMigration
{
    public function up()
    {
        [$users, $config_option_values] = $this->useTables('users', 'config_option_values');

        if ($clients = $this->execute("SELECT id, raw_additional_properties FROM {$users} WHERE type = 'Client'")) {
            foreach ($clients as $client) {
                $additional_properties = $client['raw_additional_properties'] ? unserialize($client['raw_additional_properties']) : null;

                if (empty($additional_properties)) {
                    $additional_properties = [];
                }

                if (isset($additional_properties['custom_permissions'])) {
                    foreach ($additional_properties['custom_permissions'] as $k => $custom_permission) {
                        if ($custom_permission === 'can_manage_tasks') {
                            $serialized_value = $this->executeFirstCell(
                                "SELECT value FROM {$config_option_values} WHERE name = ? AND parent_type = ? AND parent_id = ?",
                                'homepage',
                                'User',
                                $client['id'],
                            );

                            if (!empty($serialized_value)) {
                                $value = unserialize($serialized_value);

                                if ($value === 'my_subscriptions') {
                                    $this->execute(
                                        "UPDATE {$config_option_values} SET value = ? WHERE name = ? AND parent_type = ? AND parent_id = ?",
                                        serialize('my_work'),
                                        'homepage',
                                        'User',
                                        $client['id'],
                                    );
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        $this->doneUsingTables();
    }
}
