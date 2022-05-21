<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRenameTaskExpenseTrackingFeature extends AngieModelMigration
{
    public function up()
    {
        $this->renameConfigOption('task_expense_tracking_enabled', 'expense_tracking_enabled');
        $this->renameConfigOption('task_expense_tracking_enabled_lock', 'expense_tracking_enabled_lock');
    }
}
