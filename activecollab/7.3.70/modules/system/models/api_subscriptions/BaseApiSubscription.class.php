<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseApiSubscription extends ApplicationObject implements ActiveCollab\Authentication\Token\TokenInterface, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, ICreatedOn
{
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    use ICreatedOnImplementation;
    const MODEL_NAME = 'ApiSubscription';
    const MANAGER_NAME = 'ApiSubscriptions';

    protected string $table_name = 'api_subscriptions';
    protected array $fields = [
        'id',
        'user_id',
        'token_id',
        'client_name',
        'client_vendor',
        'created_on',
        'last_used_on',
        'requests_count',
    ];

    protected array $default_field_values = [
        'user_id' => 0,
        'requests_count' => 1,
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
            return $underscore ? 'api_subscription' : 'ApiSubscription';
        } else {
            return $underscore ? 'api_subscriptions' : 'ApiSubscriptions';
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
     * Return value of token_id field.
     *
     * @return string
     */
    public function getTokenId()
    {
        return $this->getFieldValue('token_id');
    }

    /**
     * Set value of token_id field.
     *
     * @param  string $value
     * @return string
     */
    public function setTokenId($value)
    {
        return $this->setFieldValue('token_id', $value);
    }

    /**
     * Return value of client_name field.
     *
     * @return string
     */
    public function getClientName()
    {
        return $this->getFieldValue('client_name');
    }

    /**
     * Set value of client_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setClientName($value)
    {
        return $this->setFieldValue('client_name', $value);
    }

    /**
     * Return value of client_vendor field.
     *
     * @return string
     */
    public function getClientVendor()
    {
        return $this->getFieldValue('client_vendor');
    }

    /**
     * Set value of client_vendor field.
     *
     * @param  string $value
     * @return string
     */
    public function setClientVendor($value)
    {
        return $this->setFieldValue('client_vendor', $value);
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
     * Return value of last_used_on field.
     *
     * @return DateTimeValue
     */
    public function getLastUsedOn()
    {
        return $this->getFieldValue('last_used_on');
    }

    /**
     * Set value of last_used_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setLastUsedOn($value)
    {
        return $this->setFieldValue('last_used_on', $value);
    }

    /**
     * Return value of requests_count field.
     *
     * @return int
     */
    public function getRequestsCount()
    {
        return $this->getFieldValue('requests_count');
    }

    /**
     * Set value of requests_count field.
     *
     * @param  int $value
     * @return int
     */
    public function setRequestsCount($value)
    {
        return $this->setFieldValue('requests_count', $value);
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
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'token_id':
                    return parent::setFieldValue($name, (string) $value);
                case 'client_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'client_vendor':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'last_used_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'requests_count':
                    return parent::setFieldValue($name, (int) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
