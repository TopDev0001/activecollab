<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseUserSession extends ApplicationObject implements ActiveCollab\Authentication\Session\SessionInterface, ICreatedOn
{
    use ICreatedOnImplementation;
    const MODEL_NAME = 'UserSession';
    const MANAGER_NAME = 'UserSessions';

    protected string $table_name = 'user_sessions';
    protected array $fields = [
        'id',
        'user_id',
        'session_id',
        'session_ttl',
        'csrf_validator',
        'created_on',
        'last_used_on',
        'requests_count',
    ];

    protected array $default_field_values = [
        'user_id' => 0,
        'session_ttl' => 0,
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
            return $underscore ? 'user_session' : 'UserSession';
        } else {
            return $underscore ? 'user_sessions' : 'UserSessions';
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
     * Return value of session_id field.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->getFieldValue('session_id');
    }

    /**
     * Set value of session_id field.
     *
     * @param  string $value
     * @return string
     */
    public function setSessionId($value)
    {
        return $this->setFieldValue('session_id', $value);
    }

    /**
     * Return value of session_ttl field.
     *
     * @return int
     */
    public function getSessionTtl()
    {
        return $this->getFieldValue('session_ttl');
    }

    /**
     * Set value of session_ttl field.
     *
     * @param  int $value
     * @return int
     */
    public function setSessionTtl($value)
    {
        return $this->setFieldValue('session_ttl', $value);
    }

    /**
     * Return value of csrf_validator field.
     *
     * @return string
     */
    public function getCsrfValidator()
    {
        return $this->getFieldValue('csrf_validator');
    }

    /**
     * Set value of csrf_validator field.
     *
     * @param  string $value
     * @return string
     */
    public function setCsrfValidator($value)
    {
        return $this->setFieldValue('csrf_validator', $value);
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
                case 'session_id':
                    return parent::setFieldValue($name, (string) $value);
                case 'session_ttl':
                    return parent::setFieldValue($name, (int) $value);
                case 'csrf_validator':
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
