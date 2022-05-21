<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddDefaultBudgetThresholdsConfigOption extends AngieModelMigration
{
    public function up()
    {
        if (AngieApplication::isOnDemand()) {
            $account_id = AngieApplication::getAccountId();
            if (!AngieApplication::shepherdAccountConfig()->getIsOccupied($account_id)) {
                $this->addConfigOption('default_budget_thresholds', [80, 100]);
            }
            $this->addConfigOption('default_budget_thresholds', null);
        } else {
            $this->addConfigOption('default_budget_thresholds', null);
        }
    }
}
