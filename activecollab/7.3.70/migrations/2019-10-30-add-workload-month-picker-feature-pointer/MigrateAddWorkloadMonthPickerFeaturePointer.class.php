<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddWorkloadMonthPickerFeaturePointer extends AngieModelMigration
{
    public function up()
    {
        $config_option = 'show_workload_month_picker';

        if (!$this->getConfigOptionValue($config_option)) {
            $this->addConfigOption($config_option, false);
        }

        /** @var User[] $users */
        $users = Users::find();

        if ($users) {
            foreach ($users as $user) {
                if (
                    ConfigOptions::hasValueFor('workload_got_it', $user) &&
                    ConfigOptions::getValueFor('workload_got_it', $user)
                ) {
                    ConfigOptions::setValueFor($config_option, $user, true);
                }
            }
        }
    }
}
