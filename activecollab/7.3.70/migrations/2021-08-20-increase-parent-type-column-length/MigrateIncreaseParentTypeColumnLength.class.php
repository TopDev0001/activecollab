<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateIncreaseParentTypeColumnLength extends AngieModelMigration
{
    public function up()
    {
        $tables = [
            'attachments' => 'parent_type',
            'reactions' => 'parent_type',
            'conversations' => 'parent_type',
            'comments' => 'parent_type',
            'activity_logs' => 'parent_type',
            'subscriptions' => 'parent_type',
            'reminders' => 'parent_type',
            'payments' => 'parent_type',
            'notifications' => 'parent_type',
            'modification_logs' => 'parent_type',
            'access_logs' => 'parent_type',
            'favorites' => 'parent_type',
            'categories' => 'parent_type',
            'invoice_items' => 'parent_type',
            'remote_invoice_items' => 'parent_type',
            'time_records' => 'parent_type',
            'expenses' => 'parent_type',
            'stopwatches' => 'parent_type',
            'email_log' => 'parent_type',
            'config_option_values' => 'parent_type',
            'parents_labels' => 'parent_type',
            'custom_hourly_rates' => 'parent_type',
            // tables with diff 'parent_type' name
            'invoices' => 'based_on_type',
            'user_invitations' => 'invited_to_type',
        ];

        foreach ($tables as $table => $column) {
            $this->execute(
                sprintf(
                    'ALTER TABLE `%s` MODIFY COLUMN `%s` VARCHAR(%s)',
                    $table,
                    $column,
                    DBStringColumn::MAX_LENGTH
                )
            );
        }
    }
}
