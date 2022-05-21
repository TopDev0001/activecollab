<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate administrators to owners.
 *
 * @package angie.migrations
 */
class MigrateAdministratorsToOwners extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $users = $this->useTables('users')[0];

        if ($administrators = $this->execute("SELECT id, raw_additional_properties FROM $users WHERE type = 'Administrator'")) {
            $owners = [];
            $tech_admins = [];

            foreach ($administrators as $administrator) {
                $additional_properties = $administrator['raw_additional_properties'] ? unserialize($administrator['raw_additional_properties']) : null;

                if (empty($additional_properties)) {
                    $additional_properties = [];
                }

                if (isset($additional_properties['custom_permissions']) && is_array($additional_properties['custom_permissions'])) {
                    $can_manage_finances = in_array('can_manage_finances', $additional_properties['custom_permissions']);
                } else {
                    $can_manage_finances = false;
                }

                // Owner
                if ($can_manage_finances) {
                    if (isset($additional_properties['custom_permissions'])) {
                        unset($additional_properties['custom_permissions']);
                    }

                    $owners[(int) $administrator['id']] = $additional_properties;

                    // Tech administrator
                } else {
                    if (isset($additional_properties['custom_permissions'])) {
                        $additional_properties['custom_permissions'] = ['can_manage_projects', 'can_manage_settings'];
                    }

                    $tech_admins[(int) $administrator['id']] = $additional_properties;
                }
            }

            foreach ($owners as $user_id => $additional_properties) {
                $this->execute("UPDATE $users SET type = 'Owner', raw_additional_properties = ? WHERE id = ?", serialize($additional_properties), $user_id);
            }

            foreach ($tech_admins as $user_id => $additional_properties) {
                $this->execute("UPDATE $users SET type = 'Member', raw_additional_properties = ? WHERE id = ?", serialize($additional_properties), $user_id);
            }
        }
    }
}
