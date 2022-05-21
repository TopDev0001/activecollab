<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateSimplifyDateFormats extends AngieModelMigration
{
    public function up()
    {
        $config_option_name = 'format_date';
        $formats_map = [
            '%b %e. %Y' => '%b %e. %Y',
            '%b %e, %Y' => '%b %e. %Y',
            '%a, %b %e, %Y' => '%b %e. %Y',
            '%e %b %Y' => '%e. %b %Y',
            '%Y/%m/%d' => '%b %e. %Y',
            '%m/%d/%Y' => '%b %e. %Y',
            '%d/%m/%y' => '%e. %b %Y',
            '%d/%m/%Y' => '%e. %b %Y',
        ];

        if ($user_ids = $this->executeFirstColumn('SELECT id FROM users')) {
            foreach ($user_ids as $user_id) {
                $condition = DB::prepare(
                    'name = ? AND parent_type = ? AND parent_id = ?',
                    $config_option_name,
                    'User',
                    $user_id
                );

                if ($value = $this->executeFirstCell("SELECT value FROM config_option_values WHERE {$condition}")) {
                    $format = unserialize($value);

                    $this->execute(
                        "UPDATE config_option_values SET value = ? WHERE {$condition}",
                        serialize($formats_map[$format])
                    );
                }
            }

            AngieApplication::cache()->remove('config_options');
        }
    }
}
