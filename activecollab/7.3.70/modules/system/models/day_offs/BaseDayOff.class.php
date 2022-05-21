<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseDayOff extends ApplicationObject implements ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, ICreatedOn, IUpdatedOn
{
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    use ICreatedOnImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'DayOff';
    const MANAGER_NAME = 'DayOffs';

    protected string $table_name = 'day_offs';
    protected array $fields = [
        'id',
        'name',
        'start_date',
        'end_date',
        'repeat_yearly',
        'created_on',
        'updated_on',
    ];

    protected array $default_field_values = [
        'name' => '',
        'repeat_yearly' => false,
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
            return $underscore ? 'day_off' : 'DayOff';
        } else {
            return $underscore ? 'day_offs' : 'DayOffs';
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
     * Return value of name field.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }

    /**
     * Set value of name field.
     *
     * @param  string $value
     * @return string
     */
    public function setName($value)
    {
        return $this->setFieldValue('name', $value);
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
     * Return value of repeat_yearly field.
     *
     * @return bool
     */
    public function getRepeatYearly()
    {
        return $this->getFieldValue('repeat_yearly');
    }

    /**
     * Set value of repeat_yearly field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setRepeatYearly($value)
    {
        return $this->setFieldValue('repeat_yearly', $value);
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
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'start_date':
                    return parent::setFieldValue($name, dateval($value));
                case 'end_date':
                    return parent::setFieldValue($name, dateval($value));
                case 'repeat_yearly':
                    return parent::setFieldValue($name, (bool) $value);
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
