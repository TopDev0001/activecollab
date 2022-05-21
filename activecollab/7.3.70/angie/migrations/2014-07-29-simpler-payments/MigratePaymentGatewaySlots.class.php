<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce payment gateway slots and set existing gateways into them.
 *
 * @package angie.migrations
 */
class MigratePaymentGatewaySlots extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $gateways = $this->useTableForAlter('payment_gateways');

        $paypal_gateway_types = ['PaypalExpressCheckoutGateway'];
        $credit_card_gateway_types = ['AuthorizeGateway', 'AuthorizeAimGateway', 'BrainTreeGateway', 'PaypalDirectGateway', 'StripeGateway'];

        // Update payment methods before we clean up the gateways table
        $this->updatePaymentMethods($gateways->getName(), $paypal_gateway_types, $credit_card_gateway_types);

        $paypal_gateway_id = (int) $this->executeFirstCell('SELECT id FROM ' . $gateways->getName() . ' WHERE type IN (?) AND is_enabled = ?', $paypal_gateway_types, true);
        $cc_gateway_id = (int) $this->executeFirstCell('SELECT id FROM ' . $gateways->getName() . ' WHERE type IN (?) AND is_enabled = ? LIMIT 0, 1', $credit_card_gateway_types, true);

        $this->addConfigOption('paypal_payment_gateway_id', $paypal_gateway_id);
        $this->addConfigOption('credit_card_gateway_id', $cc_gateway_id);

        $ids = [];

        if ($paypal_gateway_id) {
            $ids[] = $paypal_gateway_id;
        }

        if ($cc_gateway_id) {
            $ids[] = $cc_gateway_id;
        }

        if (count($ids)) {
            $this->execute('DELETE FROM ' . $gateways->getName() . ' WHERE id NOT IN (?)', $ids);
        } else {
            $this->execute('DELETE FROM ' . $gateways->getName());
        }

        $this->execute('UPDATE ' . $gateways->getName() . ' SET type = ? WHERE type = ?', 'AuthorizeGateway', 'AuthorizeAimGateway');

        $gateways->dropColumn('is_default');
        $gateways->dropColumn('is_enabled');

        $this->doneUsingTables();
    }

    /**
     * Update payment methods.
     *
     * @param string $gateways
     */
    private function updatePaymentMethods($gateways, array $paypal_gateway_types, array $credit_card_gateway_types)
    {
        $payments = $this->useTables('payments')[0];

        $paypal_gateway_ids = $this->executeFirstColumn("SELECT id FROM $gateways WHERE type IN (?)", $paypal_gateway_types);
        $credit_card_gateway_ids = $this->executeFirstColumn("SELECT id FROM $gateways WHERE type IN (?)", $credit_card_gateway_types);

        if ($paypal_gateway_ids && is_foreachable($paypal_gateway_ids)) {
            $this->execute("UPDATE $payments SET method = 'paypal' WHERE gateway_id IN (?)", $paypal_gateway_ids);
        }

        if ($credit_card_gateway_ids && is_foreachable($credit_card_gateway_ids)) {
            $this->execute("UPDATE $payments SET method = 'credit_card' WHERE gateway_id IN (?)", $credit_card_gateway_ids);
        }

        $this->execute("UPDATE $payments SET method = 'custom' WHERE method NOT IN (?)", ['paypal', 'credit_card']);
    }
}
