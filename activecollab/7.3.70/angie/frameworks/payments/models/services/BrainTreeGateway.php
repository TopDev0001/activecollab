<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Transaction;

/**
 * Braintree gateway class.
 *
 * @package angie.framework.payments
 * @subpackage models
 */
class BrainTreeGateway extends PaymentGateway implements ICardProcessingPaymentGateway
{
    use ICardProcessingPaymentGatewayImplementation;

    /**
     * Return array of supported currencies, or true if all currencies are supported.
     *
     * @return bool|array
     */
    protected function getSupportedCurrencies()
    {
        return array_keys($this->getMerchantAccountIds());
    }

    /**
     * Process credit card and return payment instance.
     *
     * @param  float                  $amount
     * @param  string                 $token
     * @param  string|null            $comment
     * @return PaymentGatewayResponse
     * @throws PaymentGatewayError
     */
    public function processCreditCard($amount, Currency $currency, $token, $comment = null)
    {
        $this->prepareAndValidatePaymentData($amount, $currency);

        Configuration::environment(($this->isLive() == 1 ? 'production' : 'sandbox'));
        Configuration::merchantId($this->getMerchantId());
        Configuration::publicKey($this->getPublicKey());
        Configuration::privateKey($this->getPrivateKey());

        try {
            /** @var object $response */
            $response = Transaction::sale([
                'amount' => $amount,
                'paymentMethodNonce' => $token,
                'merchantAccountId' => $this->getMerchantAccountIdForCurrency($currency),
                'options' => [
                    'submitForSettlement' => true,
                ],
            ]);

            if ($response->success) {
                return new PaymentGatewayResponse($amount, $response->transaction->id);
            } elseif ($response->transaction) {
                throw new PaymentGatewayError($response->message, ['processor_response_message' => $response->transaction->processorResponseText, 'processor_response_code' => $response->transaction->processorResponseCode]);
            } else {
                throw new PaymentGatewayError($response->message);
            }
        } catch (Exception $e) {
            throw new PaymentGatewayError($e->getMessage(), null, $e);
        }
    }

    public function getToken($invoice)
    {
        $this->configure();

        return ClientToken::generate();
    }

    /**
     * Configure BrainTree gateway.
     */
    private function configure()
    {
        Configuration::environment(($this->isLive() == 1 ? 'production' : 'sandbox'));
        Configuration::merchantId($this->getMerchantId());
        Configuration::publicKey($this->getPublicKey());
        Configuration::privateKey($this->getPrivateKey());
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'merchant_id' => $this->getMerchantId(),
            'merchant_account_ids' => $this->getMerchantAccountIds(),
            'public_key' => $this->getPublicKey(),
        ]);
    }

    // ---------------------------------------------------
    //  Gateway configuration
    // ---------------------------------------------------

    /**
     * Set security credentials.
     *
     * @throws InvalidParamError
     */
    public function setCredentials(array $credentials)
    {
        if (empty($credentials['merchant_id'])) {
            throw new InvalidParamError('credentials', $credentials, 'Merchant ID is required');
        }

        $this->prepareMerchantAccountIds($credentials);

        if (empty($credentials['merchant_account_ids'])) {
            throw new InvalidParamError('credentials', $credentials, 'Merchant account ID needs to be specified for the default currency');
        }

        if (empty($credentials['public_key'])) {
            throw new InvalidParamError('credentials', $credentials, 'Public key is required');
        }

        if (empty($credentials['private_key'])) {
            throw new InvalidParamError('credentials', $credentials, 'Private key is required');
        }

        $this->setMerchantId($credentials['merchant_id']);
        $this->setMerchantAccountIds($credentials['merchant_account_ids']);
        $this->setPublicKey($credentials['public_key']);
        $this->setPrivateKey($credentials['private_key']);
    }

    /**
     * Load and prepare merchant account ID-s indexed by currency.
     */
    private function prepareMerchantAccountIds(array & $credentials)
    {
        $merchant_account_ids = [];

        if (isset($credentials['merchant_account_ids']) && is_foreachable($credentials['merchant_account_ids'])) {
            if ($rows = DB::execute('SELECT code FROM currencies WHERE code IN (?)', array_keys($credentials['merchant_account_ids']))) {
                foreach ($rows as $row) {
                    $currency_code = strtoupper($row['code']);

                    foreach ($credentials['merchant_account_ids'] as $k => $v) {
                        if (trim($v) === '') {
                            continue;
                        }

                        if (strtoupper($k) === $currency_code) {
                            $merchant_account_ids[$currency_code] = trim($v);
                            break;
                        }
                    }
                }
            }
        }

        $credentials['merchant_account_ids'] = $merchant_account_ids;
    }

    public function is($var)
    {
        return $var instanceof self &&
            $var->getMerchantId() === $this->getMerchantId() &&
            $var->getMerchantAccountIds() === $this->getMerchantAccountIds() &&
            $var->getPublicKey() === $this->getPublicKey() &&
            $var->getPrivateKey() === $this->getPrivateKey() &&
            $var->isLive() === $this->isLive();
    }

    /**
     * Return merchant ID.
     */
    public function getMerchantId()
    {
        return $this->getAdditionalProperty('merchant_id');
    }

    /**
     * Set merchant ID.
     *
     * @param $value
     */
    public function setMerchantId($value)
    {
        $this->setAdditionalProperty('merchant_id', $value);
    }

    /**
     * Return merchent account ID-s.
     *
     * @return array
     */
    public function getMerchantAccountIds()
    {
        $ids = $this->getAdditionalProperty('merchant_account_ids');

        return empty($ids) ? [] : $ids;
    }

    /**
     * Return merchant account ID for the given currency.
     *
     * @return string
     * @throws PaymentGatewayError
     */
    private function getMerchantAccountIdForCurrency(Currency $currency)
    {
        $account_ids = $this->getMerchantAccountIds();
        $currency_code = $currency->getCode();

        if (empty($account_ids[$currency_code])) {
            throw new PaymentGatewayError('Merchant account not set for this currency');
        } else {
            return $account_ids[$currency_code];
        }
    }

    /**
     * Set merchent account ID-s for multiple currencies.
     *
     * Key is currency code, value is account ID for that currency
     *
     * @param array|null $value
     */
    public function setMerchantAccountIds($value)
    {
        $this->setAdditionalProperty('merchant_account_ids', $value);
    }

    /**
     * Get payment gateway public_key.
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->getAdditionalProperty('public_key');
    }

    /**
     * Set payment gateway public_key.
     *
     * @param string $value
     */
    public function setPublicKey($value)
    {
        $this->setAdditionalProperty('public_key', $value);
    }

    /**
     * Get payment gateway private_key.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->getAdditionalProperty('private_key');
    }

    /**
     * Set payment gateway private_key.
     *
     * @param string $value
     */
    public function setPrivateKey($value)
    {
        $this->setAdditionalProperty('private_key', $value);
    }
}
