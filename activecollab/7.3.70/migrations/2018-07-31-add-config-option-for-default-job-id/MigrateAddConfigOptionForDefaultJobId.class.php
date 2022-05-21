<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddConfigOptionForDefaultJobId extends AngieModelMigration
{
    public function up()
    {
        $config_option_name = 'default_job_type_id';
        $config_options_values = $this->useTables('config_option_values')[0];
        $default_config_option = JobTypes::getDefaultId();

        $this->addConfigOption($config_option_name, $default_config_option);

        $users = DB::execute('SELECT * FROM users');

        $options_batch = new DBBatchInsert(
            $config_options_values,
            ['name',  'parent_type', 'parent_id', 'value'],
            500,
            DBBatchInsert::INSERT_RECORDS
        );

        foreach ($users as $user) {
            $options_batch->insert(
                $config_option_name,
                User::class,
                $user['id'],
                serialize($default_config_option)
            );
        }

        $options_batch->done();
    }
}
