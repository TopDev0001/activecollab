<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseBudgetThresholdsNotification extends ApplicationObject
{
    const MODEL_NAME = 'BudgetThresholdsNotification';
    const MANAGER_NAME = 'BudgetThresholdsNotifications';

    protected string $table_name = 'budget_thresholds_notifications';
    protected array $fields = [
        'id',
        'project_id',
        'type',
        'threshold',
        'user_id',
        'sent_at',
    ];

    protected array $default_field_values = [
        'project_id' => 0,
        'threshold' => 0,
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
            return $underscore ? 'budget_thresholds_notification' : 'BudgetThresholdsNotification';
        } else {
            return $underscore ? 'budget_thresholds_notifications' : 'BudgetThresholdsNotifications';
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
     * Return value of sent_at field.
     *
     * @return DateTimeValue
     */
    public function getSentAt()
    {
        return $this->getFieldValue('sent_at');
    }

    /**
     * Set value of sent_at field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setSentAt($value)
    {
        return $this->setFieldValue('sent_at', $value);
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
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'sent_at':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
