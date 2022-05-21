<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\OnDemand\Models\Pricing\AccountPlanInterface;

class MigrateEnableTaskTimeTrackingForPlus extends AngieModelMigration
{
    public function up()
    {
        if (AngieApplication::isOnDemand() && $this->isPlusPlan()) {
            $this->addConfigOption('task_time_tracking_enabled', true);
            $this->addConfigOption('task_time_tracking_enabled_lock', true);
        }
    }

    private function isPlusPlan(): bool
    {
        return strtolower(
            AngieApplication::shepherdAccountConfig()->getPlan(AngieApplication::getAccountId())
        ) === AccountPlanInterface::PLUS_PLAN;
    }
}
