<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddLastLoginOnToUsersTable extends AngieModelMigration
{
    public function up()
    {
        $this->execute('DROP TRIGGER IF EXISTS new_subscription_updates_login_timestamp');
        $this->execute('CREATE TRIGGER new_subscription_updates_login_timestamp AFTER INSERT ON api_subscriptions FOR EACH ROW 
            BEGIN
                IF NEW.created_on IS NOT NULL THEN
                    UPDATE users SET last_login_on = NEW.created_on WHERE id = NEW.user_id;
                END IF;
            END');

        $this->execute('DROP TRIGGER IF EXISTS new_session_updates_login_timestamp');
        $this->execute('CREATE TRIGGER new_session_updates_login_timestamp AFTER INSERT ON user_sessions FOR EACH ROW 
            BEGIN
                IF NEW.created_on IS NOT NULL THEN
                    UPDATE users SET last_login_on = NEW.created_on WHERE id = NEW.user_id;
                END IF;
            END');

        if ($rows = $this->execute('SELECT MAX(created_on) AS "created_on", user_id FROM api_subscriptions GROUP BY user_id')) {
            $rows->setCasting(
                [
                    'created_on' => DBResult::CAST_DATETIME,
                ]
            );

            foreach ($rows as $row) {
                $this->execute(
                    'UPDATE users SET last_login_on = ? WHERE id = ?',
                    $row['created_on'],
                    $row['user_id']
                );
            }
        }
    }
}
