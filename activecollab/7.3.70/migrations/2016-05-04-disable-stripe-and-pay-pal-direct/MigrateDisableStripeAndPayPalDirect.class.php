<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateDisableStripeAndPayPalDirect extends AngieModelMigration
{
    public function up()
    {
        $credit_card_gateway_id = (int) $this->getConfigOptionValue('credit_card_gateway_id');

        if ($rows = $this->execute('SELECT id, type, raw_additional_properties FROM payment_gateways WHERE type IN (?)', [StripeGateway::class, PaypalDirectGateway::class])) {
            foreach ($rows as $row) {
                if (empty($row['raw_additional_properties'])) {
                    $this->execute('DELETE FROM payment_gateways WHERE id = ?', $row['id']);

                    $log_arguments = [
                        'payment_gateway_type' => $row['type'],
                        'payment_gateway_id' => $row['id'],
                    ];

                    if ($row['id'] == $credit_card_gateway_id) {
                        AngieApplication::log()->notice('Removed credit card payment gateway {payment_gateway_type} #{payment_gateway_id} (default)', $log_arguments);

                        $this->setConfigOptionValue('credit_card_gateway_id');
                    } else {
                        AngieApplication::log()->notice('Removed credit card payment gateway {payment_gateway_type} #{payment_gateway_id} (non-default)', $log_arguments);
                    }
                }
            }
        }
    }
}
