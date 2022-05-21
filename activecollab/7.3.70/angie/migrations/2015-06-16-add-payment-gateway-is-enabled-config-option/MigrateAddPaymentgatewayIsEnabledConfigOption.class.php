<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add enable state in config options for payment gateways.
 *
 * @package angie.migrations
 */
class MigrateAddPaymentGatewayIsEnabledConfigOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('paypal_payment_gateway_enabled', false);
        $this->addConfigOption('credit_card_gateway_enabled', false);
    }
}
