<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop allow payments configuration options.
 *
 * @package angie.frameworks.payments
 * @subpackage migrations
 */
class MigrateDropAllowPaymentsConfigOptions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('allow_payments');
        $this->removeConfigOption('allow_payments_for_invoice');
        $this->removeConfigOption('payment_methods_common');
        $this->removeConfigOption('payment_methods_credit_card');
        $this->removeConfigOption('payment_methods_online');
    }
}
