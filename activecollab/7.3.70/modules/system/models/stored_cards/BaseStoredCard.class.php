<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseStoredCard extends ApplicationObject
{
    const MODEL_NAME = 'StoredCard';
    const MANAGER_NAME = 'StoredCards';

    protected string $table_name = 'stored_cards';
    protected array $fields = [
        'id',
        'payment_gateway_id',
        'gateway_card_id',
        'brand',
        'last_four_digits',
        'expiration_month',
        'expiration_year',
        'card_holder_id',
        'card_holder_name',
        'card_holder_email',
        'address_line_1',
        'address_line_2',
        'address_zip',
        'address_city',
        'address_country',
    ];

    protected array $default_field_values = [
        'payment_gateway_id' => 0,
        'gateway_card_id' => '',
        'brand' => 'other',
        'expiration_month' => 0,
        'expiration_year' => 0,
    ];

    protected array $primary_key = [
        'id',
    ];

    public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string
    {
        if ($singular) {
            return $underscore ? 'stored_card' : 'StoredCard';
        } else {
            return $underscore ? 'stored_cards' : 'StoredCards';
        }
    }

    protected ?string $auto_increment = 'id';
    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    /**
     * Return value of id field.
     *
     * @return int
     */
    public function getId()
    {
        return $this->getFieldValue('id');
    }

    /**
     * Set value of id field.
     *
     * @param  int $value
     * @return int
     */
    public function setId($value)
    {
        return $this->setFieldValue('id', $value);
    }

    /**
     * Return value of payment_gateway_id field.
     *
     * @return int
     */
    public function getPaymentGatewayId()
    {
        return $this->getFieldValue('payment_gateway_id');
    }

    /**
     * Set value of payment_gateway_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setPaymentGatewayId($value)
    {
        return $this->setFieldValue('payment_gateway_id', $value);
    }

    /**
     * Return value of gateway_card_id field.
     *
     * @return string
     */
    public function getGatewayCardId()
    {
        return $this->getFieldValue('gateway_card_id');
    }

    /**
     * Set value of gateway_card_id field.
     *
     * @param  string $value
     * @return string
     */
    public function setGatewayCardId($value)
    {
        return $this->setFieldValue('gateway_card_id', $value);
    }

    /**
     * Return value of brand field.
     *
     * @return string
     */
    public function getBrand()
    {
        return $this->getFieldValue('brand');
    }

    /**
     * Set value of brand field.
     *
     * @param  string $value
     * @return string
     */
    public function setBrand($value)
    {
        return $this->setFieldValue('brand', $value);
    }

    /**
     * Return value of last_four_digits field.
     *
     * @return string
     */
    public function getLastFourDigits()
    {
        return $this->getFieldValue('last_four_digits');
    }

    /**
     * Set value of last_four_digits field.
     *
     * @param  string $value
     * @return string
     */
    public function setLastFourDigits($value)
    {
        return $this->setFieldValue('last_four_digits', $value);
    }

    /**
     * Return value of expiration_month field.
     *
     * @return int
     */
    public function getExpirationMonth()
    {
        return $this->getFieldValue('expiration_month');
    }

    /**
     * Set value of expiration_month field.
     *
     * @param  int $value
     * @return int
     */
    public function setExpirationMonth($value)
    {
        return $this->setFieldValue('expiration_month', $value);
    }

    /**
     * Return value of expiration_year field.
     *
     * @return int
     */
    public function getExpirationYear()
    {
        return $this->getFieldValue('expiration_year');
    }

    /**
     * Set value of expiration_year field.
     *
     * @param  int $value
     * @return int
     */
    public function setExpirationYear($value)
    {
        return $this->setFieldValue('expiration_year', $value);
    }

    /**
     * Return value of card_holder_id field.
     *
     * @return int
     */
    public function getCardHolderId()
    {
        return $this->getFieldValue('card_holder_id');
    }

    /**
     * Set value of card_holder_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCardHolderId($value)
    {
        return $this->setFieldValue('card_holder_id', $value);
    }

    /**
     * Return value of card_holder_name field.
     *
     * @return string
     */
    public function getCardHolderName()
    {
        return $this->getFieldValue('card_holder_name');
    }

    /**
     * Set value of card_holder_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setCardHolderName($value)
    {
        return $this->setFieldValue('card_holder_name', $value);
    }

    /**
     * Return value of card_holder_email field.
     *
     * @return string
     */
    public function getCardHolderEmail()
    {
        return $this->getFieldValue('card_holder_email');
    }

    /**
     * Set value of card_holder_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setCardHolderEmail($value)
    {
        return $this->setFieldValue('card_holder_email', $value);
    }

    /**
     * Return value of address_line_1 field.
     *
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->getFieldValue('address_line_1');
    }

    /**
     * Set value of address_line_1 field.
     *
     * @param  string $value
     * @return string
     */
    public function setAddressLine1($value)
    {
        return $this->setFieldValue('address_line_1', $value);
    }

    /**
     * Return value of address_line_2 field.
     *
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->getFieldValue('address_line_2');
    }

    /**
     * Set value of address_line_2 field.
     *
     * @param  string $value
     * @return string
     */
    public function setAddressLine2($value)
    {
        return $this->setFieldValue('address_line_2', $value);
    }

    /**
     * Return value of address_zip field.
     *
     * @return string
     */
    public function getAddressZip()
    {
        return $this->getFieldValue('address_zip');
    }

    /**
     * Set value of address_zip field.
     *
     * @param  string $value
     * @return string
     */
    public function setAddressZip($value)
    {
        return $this->setFieldValue('address_zip', $value);
    }

    /**
     * Return value of address_city field.
     *
     * @return string
     */
    public function getAddressCity()
    {
        return $this->getFieldValue('address_city');
    }

    /**
     * Set value of address_city field.
     *
     * @param  string $value
     * @return string
     */
    public function setAddressCity($value)
    {
        return $this->setFieldValue('address_city', $value);
    }

    /**
     * Return value of address_country field.
     *
     * @return string
     */
    public function getAddressCountry()
    {
        return $this->getFieldValue('address_country');
    }

    /**
     * Set value of address_country field.
     *
     * @param  string $value
     * @return string
     */
    public function setAddressCountry($value)
    {
        return $this->setFieldValue('address_country', $value);
    }

    /**
     * Set value of specific field.
     *
     * @param  mixed $value
     * @return mixed
     */
    public function setFieldValue(string $name, $value)
    {
        if ($value === null) {
            return parent::setFieldValue($name, null);
        } else {
            switch ($name) {
                case 'id':
                    return parent::setFieldValue($name, (int) $value);
                case 'payment_gateway_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'gateway_card_id':
                    return parent::setFieldValue($name, (string) $value);
                case 'brand':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'last_four_digits':
                    return parent::setFieldValue($name, (string) $value);
                case 'expiration_month':
                    return parent::setFieldValue($name, (int) $value);
                case 'expiration_year':
                    return parent::setFieldValue($name, (int) $value);
                case 'card_holder_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'card_holder_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'card_holder_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'address_line_1':
                    return parent::setFieldValue($name, (string) $value);
                case 'address_line_2':
                    return parent::setFieldValue($name, (string) $value);
                case 'address_zip':
                    return parent::setFieldValue($name, (string) $value);
                case 'address_city':
                    return parent::setFieldValue($name, (string) $value);
                case 'address_country':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
