<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BasePayment extends ApplicationObject implements ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IChild, ICreatedOn, ICreatedBy, IUpdatedOn, IAdditionalProperties
{
    use IChildImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IAdditionalPropertiesImplementation;
    const MODEL_NAME = 'Payment';
    const MANAGER_NAME = 'Payments';

    protected string $table_name = 'payments';
    protected array $fields = [
        'id',
        'parent_type',
        'parent_id',
        'amount',
        'currency_id',
        'status',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'paid_on',
        'comment',
        'method',
        'raw_additional_properties',
    ];

    protected array $default_field_values = [
        'amount' => 0.0,
        'currency_id' => 0,
        'status' => 'pending',
        'method' => 'custom',
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
            return $underscore ? 'payment' : 'Payment';
        } else {
            return $underscore ? 'payments' : 'Payments';
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
     * Return value of parent_type field.
     *
     * @return string
     */
    public function getParentType()
    {
        return $this->getFieldValue('parent_type');
    }

    /**
     * Set value of parent_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setParentType($value)
    {
        return $this->setFieldValue('parent_type', $value);
    }

    /**
     * Return value of parent_id field.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->getFieldValue('parent_id');
    }

    /**
     * Set value of parent_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setParentId($value)
    {
        return $this->setFieldValue('parent_id', $value);
    }

    /**
     * Return value of amount field.
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->getFieldValue('amount');
    }

    /**
     * Set value of amount field.
     *
     * @param  float $value
     * @return float
     */
    public function setAmount($value)
    {
        return $this->setFieldValue('amount', $value);
    }

    /**
     * Return value of currency_id field.
     *
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->getFieldValue('currency_id');
    }

    /**
     * Set value of currency_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCurrencyId($value)
    {
        return $this->setFieldValue('currency_id', $value);
    }

    /**
     * Return value of status field.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getFieldValue('status');
    }

    /**
     * Set value of status field.
     *
     * @param  string $value
     * @return string
     */
    public function setStatus($value)
    {
        return $this->setFieldValue('status', $value);
    }

    /**
     * Return value of created_on field.
     *
     * @return DateTimeValue
     */
    public function getCreatedOn()
    {
        return $this->getFieldValue('created_on');
    }

    /**
     * Set value of created_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setCreatedOn($value)
    {
        return $this->setFieldValue('created_on', $value);
    }

    /**
     * Return value of created_by_id field.
     *
     * @return int
     */
    public function getCreatedById()
    {
        return $this->getFieldValue('created_by_id');
    }

    /**
     * Set value of created_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCreatedById($value)
    {
        return $this->setFieldValue('created_by_id', $value);
    }

    /**
     * Return value of created_by_name field.
     *
     * @return string
     */
    public function getCreatedByName()
    {
        return $this->getFieldValue('created_by_name');
    }

    /**
     * Set value of created_by_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setCreatedByName($value)
    {
        return $this->setFieldValue('created_by_name', $value);
    }

    /**
     * Return value of created_by_email field.
     *
     * @return string
     */
    public function getCreatedByEmail()
    {
        return $this->getFieldValue('created_by_email');
    }

    /**
     * Set value of created_by_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setCreatedByEmail($value)
    {
        return $this->setFieldValue('created_by_email', $value);
    }

    /**
     * Return value of updated_on field.
     *
     * @return DateTimeValue
     */
    public function getUpdatedOn()
    {
        return $this->getFieldValue('updated_on');
    }

    /**
     * Set value of updated_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setUpdatedOn($value)
    {
        return $this->setFieldValue('updated_on', $value);
    }

    /**
     * Return value of paid_on field.
     *
     * @return DateValue
     */
    public function getPaidOn()
    {
        return $this->getFieldValue('paid_on');
    }

    /**
     * Set value of paid_on field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setPaidOn($value)
    {
        return $this->setFieldValue('paid_on', $value);
    }

    /**
     * Return value of comment field.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->getFieldValue('comment');
    }

    /**
     * Set value of comment field.
     *
     * @param  string $value
     * @return string
     */
    public function setComment($value)
    {
        return $this->setFieldValue('comment', $value);
    }

    /**
     * Return value of method field.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getFieldValue('method');
    }

    /**
     * Set value of method field.
     *
     * @param  string $value
     * @return string
     */
    public function setMethod($value)
    {
        return $this->setFieldValue('method', $value);
    }

    /**
     * Return value of raw_additional_properties field.
     *
     * @return string
     */
    public function getRawAdditionalProperties()
    {
        return $this->getFieldValue('raw_additional_properties');
    }

    /**
     * Set value of raw_additional_properties field.
     *
     * @param  string $value
     * @return string
     */
    public function setRawAdditionalProperties($value)
    {
        return $this->setFieldValue('raw_additional_properties', $value);
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
                case 'parent_type':
                    return parent::setFieldValue($name, (string) $value);
                case 'parent_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'amount':
                    return parent::setFieldValue($name, (float) $value);
                case 'currency_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'status':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'paid_on':
                    return parent::setFieldValue($name, dateval($value));
                case 'comment':
                    return parent::setFieldValue($name, (string) $value);
                case 'method':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'raw_additional_properties':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
