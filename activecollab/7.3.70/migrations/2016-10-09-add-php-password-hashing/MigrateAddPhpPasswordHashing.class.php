<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddPhpPasswordHashing extends AngieModelMigration
{
    public function up()
    {
        // Add PHP as hashed with option
        $this->useTableForAlter('users')->alterColumn(
            'password_hashed_with',
            new DBEnumColumn('password_hashed_with', ['php', 'pbkdf2', 'sha1'], 'pbkdf2')
        );

        // Generate completely random password hashes for legacy accounts
        if ($user_ids = $this->executeFirstColumn('SELECT id FROM users WHERE password_hashed_with != ?', 'pbkdf2')) {
            foreach ($user_ids as $user_id) {
                $this->execute('UPDATE users SET password = ?, password_hashed_with = ? WHERE id = ?', password_hash(make_string(40), PASSWORD_DEFAULT), 'php', $user_id);
            }
        }

        // Remove SHA1 as hashing option and set PHP as default hashing system
        $this->useTableForAlter('users')->alterColumn(
            'password_hashed_with',
            new DBEnumColumn('password_hashed_with', ['php', 'pbkdf2'], 'php')
        );
    }
}
