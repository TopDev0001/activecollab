<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateSearchSortConfigOption extends AngieModelMigration
{
    public function up()
    {
        $config_option_name = 'search_sort_preference';
        $config_options_values = $this->useTables('config_option_values')[0];

        $this->addConfigOption($config_option_name, 'score'); // default option value

        $options_batch = new DBBatchInsert(
            $config_options_values,
            [
                'name',
                'parent_type',
                'parent_id',
                'value',
            ],
            500,
            DBBatchInsert::REPLACE_RECORDS
        );

        $users = DB::execute('SELECT `id` FROM `users`');

        if (!empty($users)) {
            foreach ($users as $user) {
                $options_batch->insert(
                    $config_option_name,
                    User::class,
                    $user['id'],
                    serialize('date')
                );
            }
        }

        $options_batch->done();
    }
}
