<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseAvailabilityRecord extends ApplicationObject implements IWhoCanSeeThis, ICreatedOn, ICreatedBy, IUpdatedOn
{
    use IWhoCanSeeThisImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'AvailabilityRecord';
    const MANAGER_NAME = 'AvailabilityRecords';

    protected string $table_name = 'availability_records';
    protected array $fields = [
        'id',
        'availability_type_id',
        'user_id',
        'message',
        'start_date',
        'end_date',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
    ];

    protected array $default_field_values = [
        'availability_type_id' => 0,
        'user_id' => 0,
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
            return $underscore ? 'availability_record' : 'AvailabilityRecord';
        } else {
            return $underscore ? 'availability_records' : 'AvailabilityRecords';
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
     * Return value of availability_type_id field.
     *
     * @return int
     */
    public function getAvailabilityTypeId()
    {
        return $this->getFieldValue('availability_type_id');
    }

    /**
     * Set value of availability_type_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setAvailabilityTypeId($value)
    {
        return $this->setFieldValue('availability_type_id', $value);
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
     * Return value of message field.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getFieldValue('message');
    }

    /**
     * Set value of message field.
     *
     * @param  string $value
     * @return string
     */
    public function setMessage($value)
    {
        return $this->setFieldValue('message', $value);
    }

    /**
     * Return value of start_date field.
     *
     * @return DateValue
     */
    public function getStartDate()
    {
        return $this->getFieldValue('start_date');
    }

    /**
     * Set value of start_date field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setStartDate($value)
    {
        return $this->setFieldValue('start_date', $value);
    }

    /**
     * Return value of end_date field.
     *
     * @return DateValue
     */
    public function getEndDate()
    {
        return $this->getFieldValue('end_date');
    }

    /**
     * Set value of end_date field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setEndDate($value)
    {
        return $this->setFieldValue('end_date', $value);
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
                case 'availability_type_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'message':
                    return parent::setFieldValue($name, (string) $value);
                case 'start_date':
                    return parent::setFieldValue($name, dateval($value));
                case 'end_date':
                    return parent::setFieldValue($name, dateval($value));
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
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
