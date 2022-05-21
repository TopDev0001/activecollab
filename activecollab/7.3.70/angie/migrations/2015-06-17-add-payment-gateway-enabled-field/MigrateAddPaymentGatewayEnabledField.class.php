<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add payment gateway enabled field.
 *
 * @package angie.migrations
 */
class MigrateAddPaymentGatewayEnabledField extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $paypal_payment_gateway_id = $this->getConfigOptionValue('paypal_payment_gateway_id');
        $credit_card_gateway_id = $this->getConfigOptionValue('credit_card_gateway_id');
        $paypal_payment_gateway_enabled = (bool) $this->getConfigOptionValue('paypal_payment_gateway_enabled');
        $credit_card_gateway_enabled = (bool) $this->getConfigOptionValue('credit_card_gateway_enabled');

        $payment_gateways_table = $this->useTableForAlter('payment_gateways');
        $payment_gateways_table->addColumn(
            new DBBoolColumn('is_enabled'),
            'raw_additional_properties'
        );

        $payment_gateways_table_name = $payment_gateways_table->getName();

        DB::execute("UPDATE $payment_gateways_table_name SET is_enabled = ? WHERE id = ?", $paypal_payment_gateway_enabled, $paypal_payment_gateway_id);
        DB::execute("UPDATE $payment_gateways_table_name SET is_enabled = ? WHERE id = ?", $credit_card_gateway_enabled, $credit_card_gateway_id);

        $this->removeConfigOption('paypal_payment_gateway_enabled');
        $this->removeConfigOption('credit_card_gateway_enabled');

        $this->doneUsingTables();
    }
}
