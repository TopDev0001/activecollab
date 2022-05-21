<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Paypal common class.
 *
 * @package angie.framework.payments
 * @subpackage models
 */
abstract class PaypalGateway extends PaymentGateway
{
    const PAYPAL_DIRECT_PAYMENT_METHOD = 'DoDirectPayment';
    const PAYPAL_SET_EXPRESS_CHECKOUT_METHOD = 'SetExpressCheckout';
    const PAYPAL_GET_EXPRESS_CHECKOUT_METHOD = 'GetExpressCheckoutDetails';
    const PAYPAL_DO_EXPRESS_CHECKOUT_METHOD = 'DoExpressCheckoutPayment';

    const ENDPOINT_URL = 'https://api-3t.paypal.com/nvp';
    const TEST_URL = 'https://api-3t.sandbox.paypal.com/nvp';
    const TEST_REDIRECT_URL = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout';
    const REDIRECT_URL = 'https://www.paypal.com/webscr&cmd=_express-checkout';
    const API_VERSION = '56.0';

    /*
     * Accepted currencies
     *
     * @var array
     */
    public $supported_currencies = [
        'USD' => 'U.S. Dollar',
        'EUR' => 'Euro',
        'AUD' => 'Australian Dollar',
        'CAD' => 'Canadian Dollar',
        'JPY' => 'Japanese Yen',
        'GBP' => 'Pound Sterling',
    ];

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'api_username' => $this->getApiUsername(),
            'api_signature' => $this->getApiSignature(),
        ]);
    }

    /**
     * Send HTTP POST Request.
     *
     * @param  string      $method_name
     * @param  string      $nvp_str
     * @return array
     * @throws Angie\Error
     */
    public function callService($method_name, $nvp_str)
    {
        // Set up your API credentials, PayPal end point, and API version.
        $api_username = urlencode($this->getApiUsername());
        $api_password = urlencode($this->getApiPassword());
        $api_signature = urlencode($this->getApiSignature());

        if ($this->isLive()) {
            $api_endpoint = self::ENDPOINT_URL;
        } else {
            $api_endpoint = self::TEST_URL;
        }
        $version = urlencode(self::API_VERSION);

        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set the API operation, version, and API signature in the request.
        $nvpreq = "METHOD=$method_name&VERSION=$version&PWD=$api_password&USER=$api_username&SIGNATURE=$api_signature$nvp_str";

        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        if ($http_response = curl_exec($ch)) {
            $http_response_ar = explode('&', $http_response);

            $http_parsed_response_ar = [];
            foreach ($http_response_ar as $i => $value) {
                $tmp_ar = explode('=', $value);
                if (count($tmp_ar) > 1) {
                    $http_parsed_response_ar[$tmp_ar[0]] = urldecode($tmp_ar[1]);
                }
            }
            if ((0 == count($http_parsed_response_ar)) || !array_key_exists('ACK', $http_parsed_response_ar)) {
                AngieApplication::log()->error(
                    'Error while sending request to PayPal api endpoint.',
                    [
                        'account_id' => AngieApplication::getAccountId(),
                        'paypal_gateway' => get_class($this),
                        'paypal_endpoint' => $api_endpoint,
                        'request_params' => $nvpreq,
                        'paypal_response' => $http_response,
                    ]
                );

                throw new PaymentGatewayError("Invalid HTTP Response for POST request($nvpreq) to $api_endpoint.");
            }

            return $http_parsed_response_ar;
        } else {
            throw new PaymentGatewayError("$method_name failed: " . curl_error($ch) . '(' . curl_errno($ch) . ')');
        }
    }

    // ---------------------------------------------------
    //  Configuration
    // ---------------------------------------------------
    /**
     * Set security credentials.
     *
     * @throws InvalidParamError
     */
    public function setCredentials(array $credentials)
    {
        if (empty($credentials['api_username'])) {
            throw new InvalidParamError('credentials', $credentials, 'API username is required');
        }

        if (empty($credentials['api_password'])) {
            throw new InvalidParamError('credentials', $credentials, 'API password is required');
        }

        if (empty($credentials['api_signature'])) {
            throw new InvalidParamError('credentials', $credentials, 'API signature is required');
        }

        $this->setApiUsername($credentials['api_username']);
        $this->setApiPassword($credentials['api_password']);
        $this->setApiSignature($credentials['api_signature']);
    }

    public function is($var)
    {
        return $var instanceof self &&
            $var->getApiUsername() === $this->getApiUsername() &&
            $var->getApiPassword() === $this->getApiPassword() &&
            $var->getApiSignature() === $this->getApiSignature() &&
            $var->isLive() === $this->isLive();
    }

    /**
     * Get payment gateway api_username.
     */
    public function getApiUsername()
    {
        return $this->getAdditionalProperty('api_username');
    }

    /**
     * Set payment gateway api_username.
     *
     * @param $value
     */
    public function setApiUsername($value)
    {
        $this->setAdditionalProperty('api_username', $value);
    }

    /**
     * Get payment gateway api_password.
     */
    public function getApiPassword()
    {
        return $this->getAdditionalProperty('api_password');
    }

    /**
     * Set payment gateway api_password.
     *
     * @param $value
     */
    public function setApiPassword($value)
    {
        $this->setAdditionalProperty('api_password', $value);
    }

    /**
     * Get payment gateway api_signature.
     */
    public function getApiSignature()
    {
        return $this->getAdditionalProperty('api_signature');
    }

    /**
     * Set payment gateway api_signature.
     *
     * @param $value
     */
    public function setApiSignature($value)
    {
        $this->setAdditionalProperty('api_signature', $value);
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->getApiUsername() or $errors->fieldValueIsRequired('api_username');
        $this->getApiPassword() or $errors->fieldValueIsRequired('api_password');
        $this->getApiSignature() or $errors->fieldValueIsRequired('api_signature');

        parent::validate($errors);
    }

    /**
     * Return name value pair string from an input array.
     *
     * @return string|bool
     */
    public function prepareNvpString(array $data)
    {
        $result = '';

        foreach ($data as $k => $v) {
            $result .= '&' . $k . '=' . urlencode($v);
        }

        return $result;
    }
}
