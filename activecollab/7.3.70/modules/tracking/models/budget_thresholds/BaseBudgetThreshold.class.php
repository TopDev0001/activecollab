<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseBudgetThreshold extends ApplicationObject implements ICreatedOn, ICreatedBy, IUpdatedOn
{
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'BudgetThreshold';
    const MANAGER_NAME = 'BudgetThresholds';

    protected string $table_name = 'budget_thresholds';
    protected array $fields = [
        'id',
        'project_id',
        'type',
        'threshold',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'is_notification_sent',
        'notification_sent_on',
        'updated_on',
    ];

    protected array $default_field_values = [
        'project_id' => 0,
        'threshold' => 0,
        'is_notification_sent' => false,
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
            return $underscore ? 'budget_threshold' : 'BudgetThreshold';
        } else {
            return $underscore ? 'budget_thresholds' : 'BudgetThresholds';
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
     * Return value of project_id field.
     *
     * @return int
     */
    public function getProjectId()
    {
        return $this->getFieldValue('project_id');
    }

    /**
     * Set value of project_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setProjectId($value)
    {
        return $this->setFieldValue('project_id', $value);
    }

    /**
     * Return value of type field.
     *
     * @return string
     */
    public function getType()
    {
        return $this->getFieldValue('type');
    }

    /**
     * Set value of type field.
     *
     * @param  string $value
     * @return string
     */
    public function setType($value)
    {
        return $this->setFieldValue('type', $value);
    }

    /**
     * Return value of threshold field.
     *
     * @return int
     */
    public function getThreshold()
    {
        return $this->getFieldValue('threshold');
    }

    /**
     * Set value of threshold field.
     *
     * @param  int $value
     * @return int
     */
    public function setThreshold($value)
    {
        return $this->setFieldValue('threshold', $value);
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
     * Return value of is_notification_sent field.
     *
     * @return bool
     */
    public function getIsNotificationSent()
    {
        return $this->getFieldValue('is_notification_sent');
    }

    /**
     * Set value of is_notification_sent field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsNotificationSent($value)
    {
        return $this->setFieldValue('is_notification_sent', $value);
    }

    /**
     * Return value of notification_sent_on field.
     *
     * @return DateTimeValue
     */
    public function getNotificationSentOn()
    {
        return $this->getFieldValue('notification_sent_on');
    }

    /**
     * Set value of notification_sent_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setNotificationSentOn($value)
    {
        return $this->setFieldValue('notification_sent_on', $value);
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
                case 'project_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'type':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'threshold':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_notification_sent':
                    return parent::setFieldValue($name, (bool) $value);
                case 'notification_sent_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
