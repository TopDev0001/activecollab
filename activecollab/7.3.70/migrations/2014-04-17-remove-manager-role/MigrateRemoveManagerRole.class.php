<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Replace managers with members with extra permissions.
 *
 * @package activeCollab.modules.migrations
 * @subpackage
 */
class MigrateRemoveManagerRole extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$users] = $this->useTables('users');

        if ($managers = $this->execute("SELECT id, raw_additional_properties FROM $users WHERE type = 'Manager'")) {
            foreach ($managers as $manager) {
                $additional_properties = $manager['raw_additional_properties'] ? unserialize($manager['raw_additional_properties']) : null;

                if (empty($additional_properties)) {
                    $additional_properties = [];
                }

                if (isset($additional_properties['custom_permissions'])) {
                    foreach ($additional_properties['custom_permissions'] as $k => $custom_permission) {
                        if ($custom_permission != 'can_manage_projects' && $custom_permission != 'can_manage_finances') {
                            unset($additional_properties['custom_permissions'][$k]);
                        }
                    }
                } else {
                    $additional_properties['custom_permissions'] = [];
                }

                $this->execute("UPDATE $users SET type = 'Member', raw_additional_properties = ? WHERE id = ?", serialize($additional_properties), $manager['id']);
            }
        }

        $this->doneUsingTables();
    }
}
