<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add password policy configuration options.
 *
 * @package angie.migrations
 */
class MigrateAddPasswordPolicy extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (['password_policy_min_length' => 0, 'password_policy_require_numbers' => false, 'password_policy_require_mixed_case' => false, 'password_policy_require_symbols' => false, 'password_policy_auto_expire' => null] as $option => $value) {
            $this->addConfigOption($option, $value, false);
        }
    }
}
