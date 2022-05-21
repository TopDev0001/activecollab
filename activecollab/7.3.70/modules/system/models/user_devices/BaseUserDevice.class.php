<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseUserDevice extends ApplicationObject implements ICreatedOn, IUpdatedOn
{
    use ICreatedOnImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'UserDevice';
    const MANAGER_NAME = 'UserDevices';

    protected string $table_name = 'user_devices';
    protected array $fields = [
        'id',
        'token',
        'unique_key',
        'manufacturer',
        'user_id',
        'created_on',
        'updated_on',
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
            return $underscore ? 'user_device' : 'UserDevice';
        } else {
            return $underscore ? 'user_devices' : 'UserDevices';
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
     * Return value of token field.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getFieldValue('token');
    }

    /**
     * Set value of token field.
     *
     * @param  string $value
     * @return string
     */
    public function setToken($value)
    {
        return $this->setFieldValue('token', $value);
    }

    /**
     * Return value of unique_key field.
     *
     * @return string
     */
    public function getUniqueKey()
    {
        return $this->getFieldValue('unique_key');
    }

    /**
     * Set value of unique_key field.
     *
     * @param  string $value
     * @return string
     */
    public function setUniqueKey($value)
    {
        return $this->setFieldValue('unique_key', $value);
    }

    /**
     * Return value of manufacturer field.
     *
     * @return string
     */
    public function getManufacturer()
    {
        return $this->getFieldValue('manufacturer');
    }

    /**
     * Set value of manufacturer field.
     *
     * @param  string $value
     * @return string
     */
    public function setManufacturer($value)
    {
        return $this->setFieldValue('manufacturer', $value);
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
                case 'token':
                    return parent::setFieldValue($name, (string) $value);
                case 'unique_key':
                    return parent::setFieldValue($name, (string) $value);
                case 'manufacturer':
                    return parent::setFieldValue($name, (string) $value);
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
