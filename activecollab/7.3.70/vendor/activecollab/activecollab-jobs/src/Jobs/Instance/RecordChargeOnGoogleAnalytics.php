<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use ActiveCollab\ActiveCollabJobs\Utils\GoogleAnalytics\GoogleMeasurement;
use ActiveCollab\ShepherdSDK\Api\Users\UsersApi;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class RecordChargeOnGoogleAnalytics extends ShepherdClientJob
{
    public function __construct(array $data = null)
    {
        $requires = [
            'reference',
            'total_amount',
            'currency',
            'country',
            'instance_id',
            'user_account_id',
            'plan_name',
            'payment_gateway_plan_name',
            'access_token',
            'shepherd_url',
        ];

        foreach ($requires as $required) {
            if (empty($data[$required])) {
                throw new InvalidArgumentException("Property {$required} is required.");
            }
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $account_id = $this->getData('instance_id');
        $amount = (float) $this->getData('total_amount');

        $google_client_response = null;

        try {
            $google_measurement = new GoogleMeasurement(
                $this->getData('reference'),
                $amount,
                $this->getData('currency'),
                $this->getData('country'),
                $account_id,
                null,
                !empty($google_client_response['google_client_id']) ? $google_client_response['google_client_id'] : null
            );

            $google_measurement->addItem(
                $this->getData('plan_name'),
                $amount,
                $this->getData('payment_gateway_plan_name'),
                $this->getData('is_rebill') ? GoogleMeasurement::VARIATION_RECURRING : GoogleMeasurement::VARIATION_NEW
            );

            $google_measurement->send();
        } catch (Exception $e) {
            throw new RuntimeException("Failed to log charge on google analytics for reference {$this->getData('reference')}");
        }
    }
}
