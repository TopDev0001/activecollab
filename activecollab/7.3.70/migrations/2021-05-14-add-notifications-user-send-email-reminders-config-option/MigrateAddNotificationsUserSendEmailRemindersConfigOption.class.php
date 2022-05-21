<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddNotificationsUserSendEmailRemindersConfigOption extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('notifications_user_send_email_reminders')) {
            $this->addConfigOption('notifications_user_send_email_reminders', true);
        }

        $config_options_values = $this->useTables('config_option_values')[0];

        if ($rows = $this->execute("SELECT parent_id, value FROM $config_options_values WHERE name = ? AND parent_type = ?", 'notifications_user_send_email_mentions', 'User')) {
            foreach ($rows as $row) {
                if (!unserialize($row['value'])) {
                    $this->execute("INSERT INTO $config_options_values (name, parent_type, parent_id, value) VALUES (?, ?, ?, ?)", 'notifications_user_send_email_reminders', 'User', $row['parent_id'], serialize(false));
                }
            }
        }
    }
}
