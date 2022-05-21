<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate merchant key to merchant ID for Braintree gateways.
 *
 * @package angie.frameworks.payments
 * @subpackage migrations
 */
class MigrateMerchantKeyToMerchantId extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $payment_gateways = $this->useTables('payment_gateways')[0];

        if ($rows = $this->execute("SELECT id, raw_additional_properties FROM $payment_gateways WHERE type = 'BraintreeGateway'")) {
            foreach ($rows as $row) {
                $attributes = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : [];

                if (array_key_exists('merchant_key', $attributes)) {
                    $attributes['merchant_id'] = $attributes['merchant_key'];
                    unset($attributes['merchant_key']);
                }

                $this->execute("UPDATE $payment_gateways SET raw_additional_properties = ? WHERE id = ?", serialize($attributes), $row['id']);
            }
        }
    }
}
