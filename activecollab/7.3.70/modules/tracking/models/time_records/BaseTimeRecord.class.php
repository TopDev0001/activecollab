<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseTimeRecord extends ApplicationObject implements ITrash, IHistory, IActivityLog, ITrackingObject, IWhoCanSeeThis, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IChild, ICreatedOn, ICreatedBy, IUpdatedOn, IUpdatedBy
{
    use ITrashImplementation;
    use IHistoryImplementation;
    use IActivityLogImplementation;
    use ITrackingObjectImplementation;
    use IWhoCanSeeThisImplementation;
    use IChildImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IUpdatedByImplementation {
        ITrackingObjectImplementation::getCreatedActivityLogInstance insteadof IActivityLogImplementation;
        ITrackingObjectImplementation::getUpdatedActivityLogInstance insteadof IActivityLogImplementation;
        ITrackingObjectImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation;
    }
    const MODEL_NAME = 'TimeRecord';
    const MANAGER_NAME = 'TimeRecords';

    protected string $table_name = 'time_records';
    protected array $fields = [
        'id',
        'parent_type',
        'parent_id',
        'invoice_type',
        'invoice_item_id',
        'job_type_id',
        'record_date',
        'value',
        'user_id',
        'user_name',
        'user_email',
        'summary',
        'billable_status',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'updated_by_id',
        'updated_by_name',
        'updated_by_email',
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'source',
        'internal_rate',
        'job_type_hourly_rate',
    ];

    protected array $default_field_values = [
        'invoice_item_id' => 0,
        'job_type_id' => 0,
        'billable_status' => 0,
        'is_trashed' => false,
        'original_is_trashed' => false,
        'trashed_by_id' => 0,
        'source' => 'unknown',
        'internal_rate' => 0.0,
        'job_type_hourly_rate' => 0.0,
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
            return $underscore ? 'time_record' : 'TimeRecord';
        } else {
            return $underscore ? 'time_records' : 'TimeRecords';
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
     * Return value of invoice_type field.
     *
     * @return string
     */
    public function getInvoiceType()
    {
        return $this->getFieldValue('invoice_type');
    }

    /**
     * Set value of invoice_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setInvoiceType($value)
    {
        return $this->setFieldValue('invoice_type', $value);
    }

    /**
     * Return value of invoice_item_id field.
     *
     * @return int
     */
    public function getInvoiceItemId()
    {
        return $this->getFieldValue('invoice_item_id');
    }

    /**
     * Set value of invoice_item_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setInvoiceItemId($value)
    {
        return $this->setFieldValue('invoice_item_id', $value);
    }

    /**
     * Return value of job_type_id field.
     *
     * @return int
     */
    public function getJobTypeId()
    {
        return $this->getFieldValue('job_type_id');
    }

    /**
     * Set value of job_type_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setJobTypeId($value)
    {
        return $this->setFieldValue('job_type_id', $value);
    }

    /**
     * Return value of record_date field.
     *
     * @return DateValue
     */
    public function getRecordDate()
    {
        return $this->getFieldValue('record_date');
    }

    /**
     * Set value of record_date field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setRecordDate($value)
    {
        return $this->setFieldValue('record_date', $value);
    }

    /**
     * Return value of value field.
     *
     * @return float
     */
    public function getValue()
    {
        return $this->getFieldValue('value');
    }

    /**
     * Set value of value field.
     *
     * @param  float $value
     * @return float
     */
    public function setValue($value)
    {
        return $this->setFieldValue('value', $value);
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
     * Return value of summary field.
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->getFieldValue('summary');
    }

    /**
     * Set value of summary field.
     *
     * @param  string $value
     * @return string
     */
    public function setSummary($value)
    {
        return $this->setFieldValue('summary', $value);
    }

    /**
     * Return value of billable_status field.
     *
     * @return int
     */
    public function getBillableStatus()
    {
        return $this->getFieldValue('billable_status');
    }

    /**
     * Set value of billable_status field.
     *
     * @param  int $value
     * @return int
     */
    public function setBillableStatus($value)
    {
        return $this->setFieldValue('billable_status', $value);
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
     * Return value of updated_by_id field.
     *
     * @return int
     */
    public function getUpdatedById()
    {
        return $this->getFieldValue('updated_by_id');
    }

    /**
     * Set value of updated_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setUpdatedById($value)
    {
        return $this->setFieldValue('updated_by_id', $value);
    }

    /**
     * Return value of updated_by_name field.
     *
     * @return string
     */
    public function getUpdatedByName()
    {
        return $this->getFieldValue('updated_by_name');
    }

    /**
     * Set value of updated_by_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setUpdatedByName($value)
    {
        return $this->setFieldValue('updated_by_name', $value);
    }

    /**
     * Return value of updated_by_email field.
     *
     * @return string
     */
    public function getUpdatedByEmail()
    {
        return $this->getFieldValue('updated_by_email');
    }

    /**
     * Set value of updated_by_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setUpdatedByEmail($value)
    {
        return $this->setFieldValue('updated_by_email', $value);
    }

    /**
     * Return value of is_trashed field.
     *
     * @return bool
     */
    public function getIsTrashed()
    {
        return $this->getFieldValue('is_trashed');
    }

    /**
     * Set value of is_trashed field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsTrashed($value)
    {
        return $this->setFieldValue('is_trashed', $value);
    }

    /**
     * Return value of original_is_trashed field.
     *
     * @return bool
     */
    public function getOriginalIsTrashed()
    {
        return $this->getFieldValue('original_is_trashed');
    }

    /**
     * Set value of original_is_trashed field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setOriginalIsTrashed($value)
    {
        return $this->setFieldValue('original_is_trashed', $value);
    }

    /**
     * Return value of trashed_on field.
     *
     * @return DateTimeValue
     */
    public function getTrashedOn()
    {
        return $this->getFieldValue('trashed_on');
    }

    /**
     * Set value of trashed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setTrashedOn($value)
    {
        return $this->setFieldValue('trashed_on', $value);
    }

    /**
     * Return value of trashed_by_id field.
     *
     * @return int
     */
    public function getTrashedById()
    {
        return $this->getFieldValue('trashed_by_id');
    }

    /**
     * Set value of trashed_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setTrashedById($value)
    {
        return $this->setFieldValue('trashed_by_id', $value);
    }

    /**
     * Return value of source field.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->getFieldValue('source');
    }

    /**
     * Set value of source field.
     *
     * @param  string $value
     * @return string
     */
    public function setSource($value)
    {
        return $this->setFieldValue('source', $value);
    }

    /**
     * Return value of internal_rate field.
     *
     * @return float
     */
    public function getInternalRate()
    {
        return $this->getFieldValue('internal_rate');
    }

    /**
     * Set value of internal_rate field.
     *
     * @param  float $value
     * @return float
     */
    public function setInternalRate($value)
    {
        return $this->setFieldValue('internal_rate', $value);
    }

    /**
     * Return value of job_type_hourly_rate field.
     *
     * @return float
     */
    public function getJobTypeHourlyRate()
    {
        return $this->getFieldValue('job_type_hourly_rate');
    }

    /**
     * Set value of job_type_hourly_rate field.
     *
     * @param  float $value
     * @return float
     */
    public function setJobTypeHourlyRate($value)
    {
        return $this->setFieldValue('job_type_hourly_rate', $value);
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
                case 'invoice_type':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'invoice_item_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'job_type_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'record_date':
                    return parent::setFieldValue($name, dateval($value));
                case 'value':
                    return parent::setFieldValue($name, (float) $value);
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'user_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'user_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'summary':
                    return parent::setFieldValue($name, (string) $value);
                case 'billable_status':
                    return parent::setFieldValue($name, (int) $value);
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
                case 'updated_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'updated_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'updated_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'original_is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'source':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'internal_rate':
                    return parent::setFieldValue($name, (float) $value);
                case 'job_type_hourly_rate':
                    return parent::setFieldValue($name, (float) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
