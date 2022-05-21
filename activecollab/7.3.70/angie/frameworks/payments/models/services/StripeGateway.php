<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

/**
 * Stripe payment class.
 *
 * @package angie.framework.payments
 * @subpackage models
 */
class StripeGateway extends PaymentGateway implements ICardProcessingPaymentGateway
{
    use ICardProcessingPaymentGatewayImplementation;

    const STATUS_REQUIRES_SOURCE_ACTION = 'requires_source_action';

    /**
     * Accepted currencies.
     *
     * @var array
     */
    public $supported_currencies = 'all';

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
        $check_amount = $amount;

        $this->prepareAndValidatePaymentData($amount, $currency);

        Stripe::setApiKey($this->getApiKey());

        try {
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency->getCode(),
                'description' => ($comment) ? $comment : '',
                'payment_method' => PaymentMethod::create([
                    'type' => 'card',
                    'card' => ['token' => $token],
                ]),
            ]);
        } catch (Throwable $e) {
            throw new PaymentGatewayError();
        }

        if ($intent instanceof PaymentIntent) {
            try {
                if ($this->isAmountEqualOrCloseEnough($intent->amount, round($check_amount * 100))) {
                    $intent->confirm([
                        'return_url' => sprintf('%s/s/payment-processing',
                            ROOT_URL
                        ),
                    ]);
                } else {
                    $intent->cancel();

                    AngieApplication::log()->error('Invoice and processing amount mismatch!', [
                        'original_invoice_amount' => $check_amount,
                        'invoice_amount' => round($check_amount * 100),
                        'amount_prepared_for_stripe' => $intent->amount,
                        'invoice_currency' => $currency,
                    ]);

                    throw new PaymentGatewayError('Failed to process payment');
                }
            } catch (CardException $e) {
                  throw new PaymentGatewayError('Error while trying to make a charge: ' . $e->getMessage());
            } catch (Throwable $e) {
                AngieApplication::log()->error('Confirming customer payment intent failed', [
                    'error_message' => $e->getMessage(),
                ]);

                throw $e;
            }

            if ($intent->last_payment_error) {
                AngieApplication::log()->warning('Error while customer invoice is being paid', [
                    'payment_error_code' => $intent->last_payment_error->code,
                    'payment_error_message' => $intent->last_payment_error->message,
                ]);

                throw new PaymentGatewayError('Error: ' . $intent->last_payment_error->message);
            }

            return new StripePaymentGatewayResponse(
                $amount,
                $intent->id,
                in_array($intent->status, [
                    PaymentIntent::STATUS_REQUIRES_ACTION,
                    PaymentIntent::STATUS_REQUIRES_CONFIRMATION,
                    self::STATUS_REQUIRES_SOURCE_ACTION,
                ]),
                $intent->next_action->redirect_to_url->url,
            );
        } else {
            throw new PaymentGatewayError();
        }
    }

    public function processStripeConfirmationIntent(Payment $payment, string $payment_intent)
    {
        Stripe::setApiKey($this->getApiKey());

        $intent = PaymentIntent::retrieve($payment_intent);

        if ($intent->status === PaymentIntent::STATUS_SUCCEEDED) {
            Payments::update($payment, [
                'status' => Payment::STATUS_PAID,
                'paid_on' => DateTimeValue::now()->advance(Globalization::getGmtOffset(), false),
            ]);

            $parent = $payment->getParent();

            if ($parent instanceof IPayments) {
                $parent->logSuccessfulPayment($payment->getAmount(), $this->getType());
            }

            AngieApplication::notifications()
                ->notifyAbout('payment_received', $payment)
                ->sendToUsers($payment->getCreatedBy(), true);
        } else {
            Payments::update($payment, [
                'status' => Payment::STATUS_CANCELED,
                'updated_on' => DateTimeValue::now()->advance(Globalization::getGmtOffset(), false),
            ]);
        }

        return $payment;
    }

    public function getToken($invoice)
    {
        return $this->getPublicKey();
    }

    /**
     * Prepare amount - return amount in cents.
     *
     * @param  float $amount
     * @return int
     */
    public function prepareAmount($amount, Currency $currency)
    {
        return $this->isZeroCurrency($currency) ? ceil($amount) : ceil(round_up($amount) * 100);
    }

    /**
     * Return true if $currency is zero currency and should not be modified when amount is sent to Stripe.
     *
     * @return bool
     */
    private function isZeroCurrency(Currency $currency)
    {
        return in_array(
            $currency->getCode(),
            [
                'BIF',
                'CLP',
                'DJF',
                'GNF',
                'JPY',
                'KMF',
                'KRW',
                'MGA',
                'PYG',
                'RWF',
                'VND',
                'VUV',
                'XAF',
                'XOF',
                'XPF',
            ]);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
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
        if (isset($credentials['api_key']) && $credentials['api_key'] && isset($credentials['public_key']) && $credentials['public_key']) {
            $this->setApiKey($credentials['api_key']);
            $this->setPublicKey($credentials['public_key']);
        } else {
            throw new InvalidParamError('credentials', $credentials, 'API key and public key are required');
        }
    }

    public function is($var)
    {
        return $var instanceof self && $var->getApiKey() === $this->getApiKey();
    }

    /**
     * Get payment gateway api_key.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->getAdditionalProperty('api_key');
    }

    /**
     * Set payment gateway api_key.
     *
     * @param string $value
     */
    public function setApiKey($value)
    {
        $this->setAdditionalProperty('api_key', $value);
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

    public function validate(ValidationErrors & $errors)
    {
        $this->getApiKey() or $errors->fieldValueIsRequired('api_key');

        parent::validate($errors);
    }

    private function isAmountEqualOrCloseEnough($stripe_amount, $invoice_amount)
    {
        $amount = $stripe_amount - $invoice_amount;

        return $amount <= 2 && $amount >= 0;
    }
}
