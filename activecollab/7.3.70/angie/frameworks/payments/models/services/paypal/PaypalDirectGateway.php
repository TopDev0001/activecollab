<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Paypal direct payment class.
 *
 * @package angie.framework.payments
 * @subpackage models
 */
class PaypalDirectGateway extends PaymentGateway
{
    // PayPal Payflow constants
    const URL_TOKEN = 'https://payflowpro.paypal.com';
    const URL_TOKEN_TEST = 'https://pilot-payflowpro.paypal.com'; // sandbox

    const URL_TRANSACTION = 'https://payflowlink.paypal.com';
    const URL_TRANSACTION_TEST = 'https://pilot-payflowlink.paypal.com'; // sandbox

    /**
     * Return array of supported currencies, or true if all currencies are supported.
     *
     * @return bool|array
     */
    protected function getSupportedCurrencies()
    {
        return ['USD', 'EUR', 'AUD', 'CAD', 'JPY', 'GBP'];
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'partner_id' => $this->getPartnerId(),
            'merchant_id' => $this->getMerchantId(),
            'user_id' => $this->getUserId(),
            'processor_currency' => $this->getProcessorCurrency(),
        ]);
    }

    /**
     * Make authentication params.
     *
     * @return string
     */
    public function getApiAuthenticationParams()
    {
        // if not set up additional users on the account, USER has the same value as VENDOR (merchant id)
        $user = !empty($this->getUserId()) ? $this->getUserId() : $this->getMerchantId();

        return 'USER=' . $user .
        '&VENDOR=' . $this->getMerchantId() .
        '&PARTNER=' . $this->getPartnerId() .
        '&PWD=' . $this->getPassword() .
        '&TENDER=C' .
        '&TRXTYPE=A' .
        '&VERBOSITY=HIGH';
    }

    /**
     * Make transaction params.
     *
     * @return string
     */
    public function getTransactionParams(Invoice $invoice)
    {
        return 'CURRENCY=' . $invoice->getCurrency()->getCode() .
        '&AMT=' . $invoice->getTotal() .
        '&INVNUM=' . $invoice->getNumber() .
        '&USER1=' . $invoice->getNumber() .
        '&USER2=' . $invoice->getHash() .
        '&ERRORURL=' . ROOT_URL . '/s/payment-paypal-success' .
        '&CANCELURL=' . ROOT_URL . '/s/payment-paypal-success' .
        '&RETURNURL=' . ROOT_URL . '/s/payment-paypal-success';
    }

    /**
     * Make transparent params.
     *
     * @return string
     */
    public function getTransparentParams()
    {
        return 'TRXTYPE=S' .
        '&SILENTTRAN=TRUE' .
        '&CREATESECURETOKEN=Y' .
        '&SECURETOKENID=' . make_string(36);
    }

    /**
     * Send HTTP POST Request.
     *
     * @param $api_endpoint
     * @param $params
     * @return mixed
     */
    private function curlCall($api_endpoint, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        return curl_exec($ch);
    }

    /**
     * Get api endpoint url for user authorization.
     *
     * @return string
     */
    public function getTokenApiEndpointUrl()
    {
        return $this->isLive() ? self::URL_TOKEN : self::URL_TOKEN_TEST;
    }

    /**
     * Get api endpoint url for post sensitive credit card data.
     *
     * @return string
     */
    public function getTransactionFormEndpointUrl()
    {
        return $this->isLive() ? self::URL_TRANSACTION : self::URL_TRANSACTION_TEST;
    }

    /**
     * Send HTTP POST Request to get security token.
     *
     * @param  string      $params
     * @return array
     * @throws Angie\Error
     */
    public function getSecurityToken($params)
    {
        $api_endpoint = $this->getTokenApiEndpointUrl();

        if ($http_response = $this->curlCall($api_endpoint, $params)) {
            $http_response_ar = explode('&', $http_response);

            $http_parsed_response_ar = [];
            foreach ($http_response_ar as $i => $value) {
                $tmp_ar = explode('=', $value);
                if (count($tmp_ar) > 1) {
                    $http_parsed_response_ar[strtolower($tmp_ar[0])] = urldecode($tmp_ar[1]);
                }
            }
            if ((0 == count($http_parsed_response_ar)) || !array_key_exists('securetoken', $http_parsed_response_ar)) {
                AngieApplication::log()->error(
                    'PayPal Pro unsuccessful response when try to get security token.',
                    [
                        'account_id' => AngieApplication::getAccountId(),
                        'paypal_endpoint' => $api_endpoint,
                        'request_params' => $params,
                        'paypal_response' => $http_response,
                    ]
                );

                throw new PaymentGatewayError('Payment failed when try to get token from PayPal.');
            }

            return $http_parsed_response_ar;
        } else {
            throw new PaymentGatewayError('HTTP POST Request to get security token failed!');
        }
    }

    /**
     * Request secure token.
     *
     * @param  Invoice $invoice
     * @return mixed
     */
    public function getToken($invoice)
    {
        $params = $this->getApiAuthenticationParams();
        $params .= '&' . $this->getTransactionParams($invoice);
        $params .= '&' . $this->getTransparentParams();

        return $this->getSecurityToken($params);
    }

    /**
     * Get from post url.
     *
     * @return string
     */
    public function getFormUrl()
    {
        return $this->getTransactionFormEndpointUrl();
    }

    /**
     * Set security credentials.
     *
     * @throws InvalidParamError
     */
    public function setCredentials(array $credentials)
    {
        if (empty($credentials['partner_id'])) {
            throw new InvalidParamError('credentials', $credentials, 'Partner is required');
        }
        if (empty($credentials['merchant_id'])) {
            throw new InvalidParamError('credentials', $credentials, 'Merchant Login is required');
        }
        if (empty($credentials['password'])) {
            throw new InvalidParamError('credentials', $credentials, 'Password is required');
        }
        if (empty($credentials['processor_currency'])) {
            throw new InvalidParamError('credentials', $credentials, 'Processor currency is required');
        }
        if (!in_array($credentials['processor_currency'], $this->getSupportedCurrencies())) {
            throw new InvalidParamError('credentials', $credentials, 'Processor currency is not valid');
        }

        $this->setPartnerId($credentials['partner_id']);
        $this->setMerchantId($credentials['merchant_id']);
        $this->setUserId($credentials['user_id']);
        $this->setPassword($credentials['password']);
        $this->setProcessorCurrency($credentials['processor_currency']);
    }

    public function is($var)
    {
        return $var instanceof self &&
            $var->getPartnerId() === $this->getPartnerId() &&
            $var->getMerchantId() === $this->getMerchantId() &&
            $var->getPassword() === $this->getPassword() &&
            $var->getProcessorCurrency() === $this->getProcessorCurrency() &&
            $var->isLive() === $this->isLive();
    }

    /**
     * Get payment gateway user.
     */
    public function getUserId()
    {
        return $this->getAdditionalProperty('user_id');
    }

    /**
     * Set payment gateway user.
     *
     * @param $value
     */
    public function setUserId($value)
    {
        $this->setAdditionalProperty('user_id', $value);
    }

    /**
     * Get payment gateway password.
     */
    public function getPassword()
    {
        return $this->getAdditionalProperty('password');
    }

    /**
     * Set payment gateway password.
     *
     * @param $value
     */
    public function setPassword($value)
    {
        $this->setAdditionalProperty('password', $value);
    }

    /**
     * Get currency supported by merchant processor.
     */
    public function getProcessorCurrency()
    {
        return $this->getAdditionalProperty('processor_currency') ? $this->getAdditionalProperty('processor_currency') : 'USD';
    }

    /**
     * Set currency supported by merchant processor.
     *
     * @param $value
     */
    public function setProcessorCurrency($value)
    {
        $this->setAdditionalProperty('processor_currency', $value);
    }

    /**
     * Get secure token id.
     */
    public function getSecureTokenId()
    {
        return $this->getAdditionalProperty('secure_token_id');
    }

    /**
     * Set secure token id.
     *
     * @param $value
     */
    public function setSecureTokenId($value)
    {
        $this->setAdditionalProperty('secure_token_id', $value);
    }

    /**
     * Get merchant id.
     */
    public function getMerchantId()
    {
        return $this->getAdditionalProperty('merchant_id');
    }

    /**
     * Set merchant id.
     *
     * @param $value
     */
    public function setMerchantId($value)
    {
        $this->setAdditionalProperty('merchant_id', $value);
    }

    /**
     * Get partner id.
     */
    public function getPartnerId()
    {
        return $this->getAdditionalProperty('partner_id');
    }

    /**
     * Set partner id.
     *
     * @param $value
     */
    public function setPartnerId($value)
    {
        $this->setAdditionalProperty('partner_id', $value);
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->getPartnerId() or $errors->fieldValueIsRequired('partner_id');
        $this->getMerchantId() or $errors->fieldValueIsRequired('merchant_id');
        $this->getPassword() or $errors->fieldValueIsRequired('password');

        parent::validate($errors);
    }
}
