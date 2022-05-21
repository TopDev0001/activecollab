<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseSubscription extends ApplicationObject implements ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IChild
{
    use IChildImplementation;
    const MODEL_NAME = 'Subscription';
    const MANAGER_NAME = 'Subscriptions';

    protected string $table_name = 'subscriptions';
    protected array $fields = [
        'id',
        'parent_type',
        'parent_id',
        'user_id',
        'user_name',
        'user_email',
        'subscribed_on',
        'code',
    ];

    protected array $default_field_values = [];

    protected array $primary_key = [
        'id',
    ];

    public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string
    {
        if ($singular) {
            return $underscore ? 'subscription' : 'Subscription';
        } else {
            return $underscore ? 'subscriptions' : 'Subscriptions';
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
     * Return value of user_id field.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->getFieldValue('user_id');
    }

    /**
     * Set value of user_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setUserId($value)
    {
        return $this->setFieldValue('user_id', $value);
    }

    /**
     * Return value of user_name field.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->getFieldValue('user_name');
    }

    /**
     * Set value of user_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setUserName($value)
    {
        return $this->setFieldValue('user_name', $value);
    }

    /**
     * Return value of user_email field.
     *
     * @return string
     */
    public function getUserEmail()
    {
        return $this->getFieldValue('user_email');
    }

    /**
     * Set value of user_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setUserEmail($value)
    {
        return $this->setFieldValue('user_email', $value);
    }

    /**
     * Return value of subscribed_on field.
     *
     * @return DateTimeValue
     */
    public function getSubscribedOn()
    {
        return $this->getFieldValue('subscribed_on');
    }

    /**
     * Set value of subscribed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setSubscribedOn($value)
    {
        return $this->setFieldValue('subscribed_on', $value);
    }

    /**
     * Return value of code field.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getFieldValue('code');
    }

    /**
     * Set value of code field.
     *
     * @param  string $value
     * @return string
     */
    public function setCode($value)
    {
        return $this->setFieldValue('code', $value);
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
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'user_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'user_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'subscribed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'code':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
