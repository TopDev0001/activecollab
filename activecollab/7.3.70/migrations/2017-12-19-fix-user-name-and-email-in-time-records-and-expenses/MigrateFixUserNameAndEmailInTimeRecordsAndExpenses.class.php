<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateFixUserNameAndEmailInTimeRecordsAndExpenses extends AngieModelMigration
{
    public function up()
    {
        $users = $this->useTableForAlter('users');
        $user_rows = $this->execute('SELECT id, first_name, last_name, email FROM ' . $users->getName());

        foreach ($user_rows as $user_row) {
            // because sometimes user does not have first and last name or email
            $user_name = '';

            if ($user_row['first_name']) {
                $user_name .= $user_row['first_name'];
            }
            if ($user_row['last_name']) {
                $user_name .= ' ' . $user_row['last_name'];
            }

            $user_name = !empty($user_name) ? trim($user_name) : null;
            $user_email = $user_row['email'] ? $user_row['email'] : null;

            $this->execute(
                'UPDATE time_records SET user_name = ?, user_email = ? WHERE user_id = ?',
                $user_name,
                $user_email,
                $user_row['id']
            );

            $this->execute(
                'UPDATE expenses SET user_name = ?, user_email = ? WHERE user_id = ?',
                $user_name,
                $user_email,
                $user_row['id']
            );
        }

        $this->doneUsingTables();
    }
}
