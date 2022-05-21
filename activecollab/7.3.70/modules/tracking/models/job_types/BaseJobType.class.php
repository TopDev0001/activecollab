<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseJobType extends ApplicationObject implements IArchive, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IUpdatedOn
{
    use IArchiveImplementation;
    use IResetInitialSettingsTimestamp;
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'JobType';
    const MANAGER_NAME = 'JobTypes';

    protected string $table_name = 'job_types';
    protected array $fields = [
        'id',
        'name',
        'default_hourly_rate',
        'is_default',
        'is_archived',
        'updated_on',
    ];

    protected array $default_field_values = [
        'name' => '',
        'default_hourly_rate' => 0.0,
        'is_default' => false,
        'is_archived' => false,
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
            return $underscore ? 'job_type' : 'JobType';
        } else {
            return $underscore ? 'job_types' : 'JobTypes';
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
     * Return value of default_hourly_rate field.
     *
     * @return float
     */
    public function getDefaultHourlyRate()
    {
        return $this->getFieldValue('default_hourly_rate');
    }

    /**
     * Set value of default_hourly_rate field.
     *
     * @param  float $value
     * @return float
     */
    public function setDefaultHourlyRate($value)
    {
        return $this->setFieldValue('default_hourly_rate', $value);
    }

    /**
     * Return value of is_default field.
     *
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->getFieldValue('is_default');
    }

    /**
     * Set value of is_default field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsDefault($value)
    {
        return $this->setFieldValue('is_default', $value);
    }

    /**
     * Return value of is_archived field.
     *
     * @return bool
     */
    public function getIsArchived()
    {
        return $this->getFieldValue('is_archived');
    }

    /**
     * Set value of is_archived field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsArchived($value)
    {
        return $this->setFieldValue('is_archived', $value);
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
                case 'default_hourly_rate':
                    return parent::setFieldValue($name, (float) $value);
                case 'is_default':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_archived':
                    return parent::setFieldValue($name, (bool) $value);
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
