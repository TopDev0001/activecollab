<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateThankYouEnumForBillingOrderTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            $billing_orders = $this->useTableForAlter('billing_orders');

            $possibilities = [
                'trial_monthly',
                'trial_yearly',
                'plan_smaller_to_bigger',
                'plan_bigger_to_smaller',
                'period_monthly_to_yearly',
                'period_yearly_to_monthly',
                'trial_suspension',
                'paid_suspension',
                'failed_payment',
                'add_on_activated',
            ];

            if ($billing_orders->getColumn('thank_you')) {
                $billing_orders->alterColumn(
                    'thank_you',
                    new DBEnumColumn(
                        'thank_you',
                        $possibilities,
                        null
                    )
                );
            }
        }
    }
}
