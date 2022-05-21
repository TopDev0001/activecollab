<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

$this->mapResource(
    'payments',
    [
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);

$this->map(
    'public_payments',
    'public_payments',
    [
        'controller' => 'public_payments',
        'action' => [
            'GET' => 'view',
            'POST' => 'add',
            'PUT' => 'update',
            'DELETE' => 'cancel',
        ],
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);

$this->map(
    'public_payment_authorizenet_confirm',
    'public_payments/authorizenet-confirm',
    [
        'controller' => 'public_payments',
        'action' => [
            'GET' => 'authorizenet_confirm',
        ],
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);

$this->map(
    'public_payment_authorizenet_form',
    'public_payments/authorizenet-form',
    [
        'controller' => 'public_payments',
        'action' => [
            'GET' => 'authorizenet_form',
        ],
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);

$this->map(
    'payment_gateways',
    'payment-gateways',
    [
        'controller' => 'payment_gateways',
        'action' => [
            'GET' => 'get_settings',
            'PUT' => 'update_settings',
        ],
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);

$this->map(
    'payment_gateway_clear_paypal',
    'payment-gateways/clear-paypal',
    [
        'controller' => 'payment_gateways',
        'action' => [
            'DELETE' => 'clear_paypal',
        ],
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);

$this->map(
    'payment_gateway_clear_credit_card',
    'payment-gateways/clear-credit-card',
    [
        'controller' => 'payment_gateways',
        'action' => [
            'DELETE' => 'clear_credit_card',
        ],
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);

$this->map(
    'payment_process_credit_card',
    'public-payments/process-stripe-confirmation-intent',
    [
        'controller' => 'public_payments',
        'action' => [
            'POST' => 'process_stripe_confirmation_intent',
        ],
        'module' => PaymentsFramework::INJECT_INTO,
    ]
);
