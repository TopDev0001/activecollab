<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateResetEmptyUserOptions extends AngieModelMigration
{
    public function up()
    {
        $this->execute('DELETE FROM config_option_values WHERE parent_type = ? AND value = ?', 'User', serialize(''));

        if ($user_ids = $this->executeFirstColumn('SELECT id FROM users WHERE is_trashed = ?', false)) {
            $now = DB::escape(serialize(time()));

            foreach ($user_ids as $user_id) {
                $this->execute("REPLACE INTO config_option_values (name, parent_type, parent_id, value) VALUES ('new_features_timestamp', 'User', ?, $now)", $user_id);
            }
        }
    }
}
