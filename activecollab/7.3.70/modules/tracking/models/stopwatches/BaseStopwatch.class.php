<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseStopwatch extends ApplicationObject implements ICreatedOn, IUpdatedOn, IWhoCanSeeThis, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IChild
{
    use ICreatedOnImplementation;
    use IUpdatedOnImplementation;
    use IWhoCanSeeThisImplementation;
    use IChildImplementation;
    const MODEL_NAME = 'Stopwatch';
    const MANAGER_NAME = 'Stopwatches';

    protected string $table_name = 'stopwatches';
    protected array $fields = [
        'id',
        'parent_type',
        'parent_id',
        'user_id',
        'user_name',
        'user_email',
        'started_on',
        'is_kept',
        'elapsed',
        'created_on',
        'updated_on',
        'notification_sent_at',
    ];

    protected array $default_field_values = [
        'is_kept' => 0,
        'elapsed' => 0,
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
            return $underscore ? 'stopwatch' : 'Stopwatch';
        } else {
            return $underscore ? 'stopwatches' : 'Stopwatches';
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
     * Return value of started_on field.
     *
     * @return DateTimeValue
     */
    public function getStartedOn()
    {
        return $this->getFieldValue('started_on');
    }

    /**
     * Set value of started_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setStartedOn($value)
    {
        return $this->setFieldValue('started_on', $value);
    }

    /**
     * Return value of is_kept field.
     *
     * @return int
     */
    public function getIsKept()
    {
        return $this->getFieldValue('is_kept');
    }

    /**
     * Set value of is_kept field.
     *
     * @param  int $value
     * @return int
     */
    public function setIsKept($value)
    {
        return $this->setFieldValue('is_kept', $value);
    }

    /**
     * Return value of elapsed field.
     *
     * @return int
     */
    public function getElapsed()
    {
        return $this->getFieldValue('elapsed');
    }

    /**
     * Set value of elapsed field.
     *
     * @param  int $value
     * @return int
     */
    public function setElapsed($value)
    {
        return $this->setFieldValue('elapsed', $value);
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
     * Return value of notification_sent_at field.
     *
     * @return DateTimeValue
     */
    public function getNotificationSentAt()
    {
        return $this->getFieldValue('notification_sent_at');
    }

    /**
     * Set value of notification_sent_at field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setNotificationSentAt($value)
    {
        return $this->setFieldValue('notification_sent_at', $value);
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
                case 'started_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'is_kept':
                    return parent::setFieldValue($name, (int) $value);
                case 'elapsed':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'notification_sent_at':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
