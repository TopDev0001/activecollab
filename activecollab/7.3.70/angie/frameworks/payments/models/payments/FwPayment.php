<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

/**
 * Framework level payment instance implementation.
 *
 * @package angie.frameworks.payments
 * @subpackage models
 */
abstract class FwPayment extends BasePayment implements RoutingContextInterface
{
    const CUSTOM = 'custom';
    const PAYPAL = 'paypal';
    const CREDIT_CARD = 'credit_card';

    const ALLOW_PARTIAL = 2;
    const ALLOW_FULL = 1;
    const DO_NOT_ALLOW = 0;
    const USE_SYSTEM_DEFAULT = -1;

    const STATUS_PAID = 'paid';
    const STATUS_PENDING = 'pending';
    const STATUS_DELETED = 'deleted';
    const STATUS_CANCELED = 'canceled';

    public function getRoutingContext(): string
    {
        return 'payment';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'payment_id' => $this->getId(),
        ];
    }

    /**
     * Return true if this is a custom payment (not paid via a gateway).
     *
     * @return bool
     */
    public function isCustom()
    {
        return $this->getMethod() === Payment::CUSTOM;
    }

    /**
     * Prepare for JSON.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'amount' => $this->getAmount(),
            'status' => $this->getStatus(),
            'paid_on' => $this->getPaidOn(),
            'currency_id' => $this->getCurrencyId(),
            'method' => $this->getMethod(),
            'method_formatted' => $this->getMethodVerbose(),
            'comment' => $this->getComment(),
            'additional_properties' => $this->getAdditionalProperties(),
            'created_by' => [
                'email' => $this->getCreatedByEmail(),
                'name' => $this->getCreatedByName(),
                'id' => $this->getCreatedById(),
            ],
            'hash' => $this->getHash(),
        ]);
    }

    /**
     * Make payment hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->getAdditionalProperty('hash');
    }

    /**
     * Make payment token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getAdditionalProperty('token');
    }

    /**
     * Make payment name on the card.
     *
     * @return string
     */
    public function getNameOnCard()
    {
        return $this->getAdditionalProperty('name_on_card');
    }

    /**
     * Return payment method verbose.
     *
     * @return string
     */
    public function getMethodVerbose()
    {
        if ($this->getMethod() == Payment::CUSTOM) {
            return lang('Custom');
        } elseif ($this->getMethod() == Payment::PAYPAL) {
            return lang('PayPal');
        } elseif ($this->getMethod() == Payment::CREDIT_CARD) {
            return lang('By Credit Card');
        } else {
            return lang('Unknown');
        }
    }

    /**
     * Response from service.
     *
     * @var array
     */
    public $response;

    /**
     * Is error occurred in payment proccess.
     *
     * @var bool
     */
    public $is_error = false;

    /**
     * Error message.
     *
     * @var string
     */
    public $error_message;

    /**
     * Return is_error flag.
     *
     * @return bool
     */
    public function getIsError()
    {
        return $this->is_error;
    }

    /**
     * Set is_error flag.
     *
     * @return bool
     */
    public function setIsError($value)
    {
        return $this->is_error = $value;
    }

    /**
     * Return error_message flag.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }

    /**
     * Set is_error flag.
     *
     * @return string
     */
    public function setErrorMessage($value)
    {
        return $this->error_message = $value;
    }

    /**
     * Return true if this payment has status paid.
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->getStatus() == Payment::STATUS_PAID;
    }

    /**
     * Return payment currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return DataObjectPool::get(Currency::class, $this->getCurrencyId());
    }

    public function isParentOptional(): bool
    {
        return false;
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if ($this->getAmount() < 0.01) {
            $errors->addError('Minumum value for your payment amount is 0.01', 'amount');
        }

        parent::validate($errors);
    }
}
