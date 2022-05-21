<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddConfigOptionForThemeModal extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('theme', 'indigo');
        $this->addConfigOption('show_theme_modal', false);

        $user_ids = DB::executeFirstColumn('SELECT `id` FROM `users` WHERE `first_login_on` IS NOT NULL');

        if (!empty($user_ids)) {
            $this->execute(
                'UPDATE config_option_values SET `value` = ? WHERE `name` = ? AND `parent_type` IN (?) AND `parent_id` IN (?)',
                serialize('classic'),
                'theme',
                [
                    Client::class,
                    Member::class,
                    Owner::class,
                    User::class,
                ],
                $user_ids
            );

            $config_options_values = $this->useTables('config_option_values')[0];

            $options_batch = new DBBatchInsert(
                $config_options_values,
                [
                    'name',
                    'parent_type',
                    'parent_id',
                    'value',
                ],
                500,
                DBBatchInsert::INSERT_RECORDS
            );

            foreach ($user_ids as $user_id) {
                $options_batch->insert(
                    'show_theme_modal',
                    User::class,
                    $user_id,
                    serialize(true)
                );
            }

            $options_batch->done();
        }
    }
}
