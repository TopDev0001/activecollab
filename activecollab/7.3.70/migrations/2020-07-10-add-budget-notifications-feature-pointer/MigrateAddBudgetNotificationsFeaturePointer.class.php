<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Model\FeaturePointer\BudgetNotificationsFeaturePointer;

class MigrateAddBudgetNotificationsFeaturePointer extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $this->execute(
                'INSERT INTO feature_pointers (type, parent_id, created_on) VALUES (?, ?, ?)',
                BudgetNotificationsFeaturePointer::class,
                null,
                new DateTimeValue()
            );
        }
    }
}
