<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseAccessLog extends ApplicationObject implements ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IChild
{
    use IChildImplementation;
    const MODEL_NAME = 'AccessLog';
    const MANAGER_NAME = 'AccessLogs';

    protected string $table_name = 'access_logs';
    protected array $fields = [
        'id',
        'parent_type',
        'parent_id',
        'accessed_by_id',
        'accessed_by_name',
        'accessed_by_email',
        'accessed_on',
        'ip_address',
        'is_download',
    ];

    protected array $default_field_values = [
        'is_download' => false,
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
            return $underscore ? 'access_log' : 'AccessLog';
        } else {
            return $underscore ? 'access_logs' : 'AccessLogs';
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
     * Return value of accessed_by_id field.
     *
     * @return int
     */
    public function getAccessedById()
    {
        return $this->getFieldValue('accessed_by_id');
    }

    /**
     * Set value of accessed_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setAccessedById($value)
    {
        return $this->setFieldValue('accessed_by_id', $value);
    }

    /**
     * Return value of accessed_by_name field.
     *
     * @return string
     */
    public function getAccessedByName()
    {
        return $this->getFieldValue('accessed_by_name');
    }

    /**
     * Set value of accessed_by_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setAccessedByName($value)
    {
        return $this->setFieldValue('accessed_by_name', $value);
    }

    /**
     * Return value of accessed_by_email field.
     *
     * @return string
     */
    public function getAccessedByEmail()
    {
        return $this->getFieldValue('accessed_by_email');
    }

    /**
     * Set value of accessed_by_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setAccessedByEmail($value)
    {
        return $this->setFieldValue('accessed_by_email', $value);
    }

    /**
     * Return value of accessed_on field.
     *
     * @return DateTimeValue
     */
    public function getAccessedOn()
    {
        return $this->getFieldValue('accessed_on');
    }

    /**
     * Set value of accessed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setAccessedOn($value)
    {
        return $this->setFieldValue('accessed_on', $value);
    }

    /**
     * Return value of ip_address field.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->getFieldValue('ip_address');
    }

    /**
     * Set value of ip_address field.
     *
     * @param  string $value
     * @return string
     */
    public function setIpAddress($value)
    {
        return $this->setFieldValue('ip_address', $value);
    }

    /**
     * Return value of is_download field.
     *
     * @return bool
     */
    public function getIsDownload()
    {
        return $this->getFieldValue('is_download');
    }

    /**
     * Set value of is_download field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsDownload($value)
    {
        return $this->setFieldValue('is_download', $value);
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
                case 'accessed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'accessed_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'accessed_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'accessed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'ip_address':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_download':
                    return parent::setFieldValue($name, (bool) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
