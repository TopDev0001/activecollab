<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Authorize payment gateway common class.
 *
 * @package angie.framework.payments
 * @subpackage models
 */
class AuthorizeGateway extends PaymentGateway implements ICardProcessingPaymentGateway
{
    use ICardProcessingPaymentGatewayImplementation;

    // const PAYMENT_URL = 'https://secure.authorize.net/profile/addPayment';
    // const PAYMENT_SANDBOX_URL = 'https://test.authorize.net/profile/addPayment';

    const PAYMENT_URL = 'https://accept.authorize.net/customer/addPayment';
    const PAYMENT_SANDBOX_URL = 'https://test.authorize.net/customer/addPayment';

    /**
     * Return array of supported currencies, or true if all currencies are supported.
     *
     * @return bool|array
     */
    protected function getSupportedCurrencies()
    {
        return ['USD'];
    }

    /**
     * Process credit card and return payment instance.
     *
     * @param  float                  $amount
     * @param  string                 $token
     * @param  string                 $comment
     * @return PaymentGatewayResponse
     * @throws PaymentGatewayError
     */
    public function processCreditCard($amount, Currency $currency, $token, $comment = null)
    {
        $transactionType = 'AuthCapture';
        $transaction = new AuthorizeNetTransaction();
        $transaction->amount = $amount;
        $transaction->customerProfileId = $this->getCustomerProfileId($token);
        $transaction->customerPaymentProfileId = $this->getCustomerPaymentProfileId($transaction->customerProfileId);

        $response = $this->getDataService()->createCustomerProfileTransaction($transactionType, $transaction);

        if ($response instanceof AuthorizeNetCIM_Response) {
            if (!$response->isOk()) {
                throw new PaymentGatewayError($response->getErrorMessage());
            } else {
                AngieApplication::memories()->forget($this->getMemoryKey($token));

                return new PaymentGatewayResponse($amount, $response->getMessageCode());
            }
        } else {
            throw new PaymentGatewayError();
        }
    }

    public function getToken($invoice)
    {
        $response = $this->getDataService()->getHostedProfilePageRequest(
            $this->getCustomerProfileId($invoice->getId()),
            [
                'hostedProfileIFrameCommunicatorUrl' => ROOT_URL . '/api/v1/public_payments/authorizenet-confirm',
            ]
        );

        if ($response instanceof AuthorizeNetCIM_Response) {
            if ($response->isOk()) {
                return (string) $response->xml->token;
            } else {
                throw new PaymentGatewayError($response->getErrorMessage());
            }
        }

        throw new PaymentGatewayError();
    }

    /**
     * @param  int    $invoice_id
     * @return string
     */
    private function getMemoryKey($invoice_id)
    {
        return 'AuthorizeNet_Invoice_' . $invoice_id;
    }

    /**
     * @param $invoice_id
     * @return mixed|string
     * @throws PaymentGatewayError
     */
    private function getCustomerProfileId($invoice_id)
    {
        $key = $this->getMemoryKey($invoice_id);
        $customer_profile_id = AngieApplication::memories()->get($key);

        if ($customer_profile_id) {
            return $customer_profile_id;
        } else {
            $customer = new AuthorizeNetCustomer();
            $customer->merchantCustomerId = $invoice_id;
            $customer_profile = $this->getDataService()->createCustomerProfile($customer);
            if ($customer_profile instanceof AuthorizeNetCIM_Response) {
                if ($customer_profile->isOk()) {
                    $customer_profile_id = $customer_profile->getCustomerProfileId();
                    AngieApplication::memories()->set($key, $customer_profile_id);

                    return $customer_profile_id;
                } else {
                    throw new PaymentGatewayError($customer_profile->getErrorMessage());
                }
            }
        }
    }

    /**
     * @param $profile_id
     * @return mixed
     * @throws PaymentGatewayError
     */
    private function getCustomerPaymentProfileId($profile_id)
    {
        $response = $this->getDataService()->getCustomerProfile($profile_id);

        if ($response instanceof AuthorizeNetCIM_Response) {
            if ($response->isOk()) {
                $id = (string) $response->xml->profile->paymentProfiles->customerPaymentProfileId;
                if (!$id) {
                    throw new PaymentGatewayError('Payment profile is not created!');
                }

                return $id;
            } else {
                throw new PaymentGatewayError($response->getErrorMessage());
            }
        }
        throw new PaymentGatewayError();
    }

    /**
     * @return mixed
     */
    public function getFormUrl()
    {
        return $this->isLive() ? self::PAYMENT_URL : self::PAYMENT_SANDBOX_URL;
    }

    /**
     * @return AuthorizeNetCIM
     */
    private function getDataService()
    {
        $authorize_net = new AuthorizeNetCIM($this->getApiLoginId(), $this->getTransactionKey());
        $authorize_net->setSandbox(!$this->isLive());

        return $authorize_net;
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'api_login_id' => $this->getApiLoginId(),
            ]
        );
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
        if (empty($credentials['api_login_id'])) {
            throw new InvalidParamError('credentials', $credentials, 'API login ID is required');
        }

        if (empty($credentials['transaction_key'])) {
            throw new InvalidParamError('credentials', $credentials, 'Transaction key is required');
        }

        $this->setApiLoginId($credentials['api_login_id']);
        $this->setTransactionKey($credentials['transaction_key']);
    }

    public function is($var)
    {
        return $var instanceof self &&
            $var->getApiLoginId() === $this->getApiLoginId() &&
            $var->getTransactionKey() === $this->getTransactionKey() &&
            $var->isLive() === $this->isLive();
    }

    /**
     * Get gateway API login ID.
     *
     * @return string
     */
    public function getApiLoginId()
    {
        return defined('AUTHORIZENET_API_LOGIN_ID') && AngieApplication::isOnDemand()
            ? AUTHORIZENET_API_LOGIN_ID
            : $this->getAdditionalProperty('api_login_id');
    }

    /**
     * Set gateway API login ID.
     *
     * @param string $value
     */
    public function setApiLoginId($value)
    {
        $this->setAdditionalProperty('api_login_id', $value);
    }

    /**
     * Get transaction key.
     *
     * @return string
     */
    public function getTransactionKey()
    {
        return defined('AUTHORIZENET_TRANSACTION_KEY') && AngieApplication::isOnDemand()
            ? AUTHORIZENET_TRANSACTION_KEY
            : $this->getAdditionalProperty('transaction_key');
    }

    /**
     * Set transaction key.
     *
     * @param string $value
     */
    public function setTransactionKey($value)
    {
        $this->setAdditionalProperty('transaction_key', $value);
    }

    public function validate(ValidationErrors & $errors)
    {
        $this->getApiLoginId() or $errors->fieldValueIsRequired('api_login_id');
        $this->getTransactionKey() or $errors->fieldValueIsRequired('transaction_key');

        parent::validate($errors);
    }
}
