<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Model\Categories\ICategoriesContext;
use ActiveCollab\Module\System\Model\Categories\ICategoriesContextImplementation;
use ActiveCollab\Module\System\Model\Categories\ICategory;
use ActiveCollab\Module\System\Model\Categories\ICategoryImplementation;
use Angie\Search\SearchItem\SearchItemImplementation;
use Angie\Search\SearchItem\SearchItemInterface;

abstract class BaseProject extends ApplicationObject implements IComplete, IMembers, ICategory, ICategoriesContext, ILabel, ITrash, IFavorite, IHistory, IActivityLog, SearchItemInterface, IAccessLog, ITracking, IBudgetThresholds, IInvoiceBasedOn, IHourlyRates, ICreatedOn, ICreatedBy, IUpdatedOn, IUpdatedBy
{
    use ICompleteImplementation;
    use IMembersViaConnectionTableImplementation;
    use ICategoryImplementation;
    use ICategoriesContextImplementation;
    use ILabelImplementation;
    use ITrashImplementation;
    use IFavoriteImplementation;
    use IHistoryImplementation;
    use IActivityLogImplementation;
    use SearchItemImplementation;
    use IAccessLogImplementation;
    use ITrackingImplementation;
    use IBudgetThresholdsImplementation;
    use IInvoiceBasedOnTrackingFilterResultImplementation;
    use IHourlyRatesImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IUpdatedByImplementation;
    public const MODEL_NAME = 'Project';
    public const MANAGER_NAME = 'Projects';

    protected string $table_name = 'projects';
    protected array $fields = [
        'id',
        'based_on_type',
        'based_on_id',
        'company_id',
        'category_id',
        'label_id',
        'currency_id',
        'budget_type',
        'budget',
        'name',
        'leader_id',
        'body',
        'completed_on',
        'completed_by_id',
        'completed_by_name',
        'completed_by_email',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'updated_by_id',
        'updated_by_name',
        'updated_by_email',
        'last_activity_on',
        'project_number',
        'project_hash',
        'is_tracking_enabled',
        'is_billable',
        'members_can_change_billable',
        'is_client_reporting_enabled',
        'is_trashed',
        'trashed_on',
        'trashed_by_id',
        'is_sample',
    ];

    protected array $default_field_values = [
        'company_id' => 0,
        'category_id' => 0,
        'label_id' => 0,
        'currency_id' => 0,
        'budget_type' => 'pay_as_you_go',
        'name' => '',
        'leader_id' => 0,
        'project_number' => 0,
        'is_tracking_enabled' => true,
        'is_billable' => true,
        'members_can_change_billable' => true,
        'is_client_reporting_enabled' => false,
        'is_trashed' => false,
        'trashed_by_id' => 0,
        'is_sample' => false,
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
            return $underscore ? 'project' : 'Project';
        } else {
            return $underscore ? 'projects' : 'Projects';
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
     * Return value of based_on_type field.
     *
     * @return string
     */
    public function getBasedOnType()
    {
        return $this->getFieldValue('based_on_type');
    }

    /**
     * Set value of based_on_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setBasedOnType($value)
    {
        return $this->setFieldValue('based_on_type', $value);
    }

    /**
     * Return value of based_on_id field.
     *
     * @return int
     */
    public function getBasedOnId()
    {
        return $this->getFieldValue('based_on_id');
    }

    /**
     * Set value of based_on_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setBasedOnId($value)
    {
        return $this->setFieldValue('based_on_id', $value);
    }

    /**
     * Return value of company_id field.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->getFieldValue('company_id');
    }

    /**
     * Set value of company_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCompanyId($value)
    {
        return $this->setFieldValue('company_id', $value);
    }

    /**
     * Return value of category_id field.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->getFieldValue('category_id');
    }

    /**
     * Set value of category_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCategoryId($value)
    {
        return $this->setFieldValue('category_id', $value);
    }

    /**
     * Return value of label_id field.
     *
     * @return int
     */
    public function getLabelId()
    {
        return $this->getFieldValue('label_id');
    }

    /**
     * Set value of label_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setLabelId($value)
    {
        return $this->setFieldValue('label_id', $value);
    }

    /**
     * Return value of currency_id field.
     *
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->getFieldValue('currency_id');
    }

    /**
     * Set value of currency_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCurrencyId($value)
    {
        return $this->setFieldValue('currency_id', $value);
    }

    /**
     * Return value of budget_type field.
     *
     * @return string
     */
    public function getBudgetType()
    {
        return $this->getFieldValue('budget_type');
    }

    /**
     * Set value of budget_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setBudgetType($value)
    {
        return $this->setFieldValue('budget_type', $value);
    }

    /**
     * Return value of budget field.
     *
     * @return float
     */
    public function getBudget()
    {
        return $this->getFieldValue('budget');
    }

    /**
     * Set value of budget field.
     *
     * @param  float $value
     * @return float
     */
    public function setBudget($value)
    {
        return $this->setFieldValue('budget', $value);
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
     * Return value of leader_id field.
     *
     * @return int
     */
    public function getLeaderId()
    {
        return $this->getFieldValue('leader_id');
    }

    /**
     * Set value of leader_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setLeaderId($value)
    {
        return $this->setFieldValue('leader_id', $value);
    }

    /**
     * Return value of body field.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getFieldValue('body');
    }

    /**
     * Set value of body field.
     *
     * @param  string $value
     * @return string
     */
    public function setBody($value)
    {
        return $this->setFieldValue('body', $value);
    }

    /**
     * Return value of completed_on field.
     *
     * @return DateTimeValue
     */
    public function getCompletedOn()
    {
        return $this->getFieldValue('completed_on');
    }

    /**
     * Set value of completed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setCompletedOn($value)
    {
        return $this->setFieldValue('completed_on', $value);
    }

    /**
     * Return value of completed_by_id field.
     *
     * @return int
     */
    public function getCompletedById()
    {
        return $this->getFieldValue('completed_by_id');
    }

    /**
     * Set value of completed_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCompletedById($value)
    {
        return $this->setFieldValue('completed_by_id', $value);
    }

    /**
     * Return value of completed_by_name field.
     *
     * @return string
     */
    public function getCompletedByName()
    {
        return $this->getFieldValue('completed_by_name');
    }

    /**
     * Set value of completed_by_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setCompletedByName($value)
    {
        return $this->setFieldValue('completed_by_name', $value);
    }

    /**
     * Return value of completed_by_email field.
     *
     * @return string
     */
    public function getCompletedByEmail()
    {
        return $this->getFieldValue('completed_by_email');
    }

    /**
     * Set value of completed_by_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setCompletedByEmail($value)
    {
        return $this->setFieldValue('completed_by_email', $value);
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
     * Return value of last_activity_on field.
     *
     * @return DateTimeValue
     */
    public function getLastActivityOn()
    {
        return $this->getFieldValue('last_activity_on');
    }

    /**
     * Set value of last_activity_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setLastActivityOn($value)
    {
        return $this->setFieldValue('last_activity_on', $value);
    }

    /**
     * Return value of project_number field.
     *
     * @return int
     */
    public function getProjectNumber()
    {
        return $this->getFieldValue('project_number');
    }

    /**
     * Set value of project_number field.
     *
     * @param  int $value
     * @return int
     */
    public function setProjectNumber($value)
    {
        return $this->setFieldValue('project_number', $value);
    }

    /**
     * Return value of project_hash field.
     *
     * @return string
     */
    public function getProjectHash()
    {
        return $this->getFieldValue('project_hash');
    }

    /**
     * Set value of project_hash field.
     *
     * @param  string $value
     * @return string
     */
    public function setProjectHash($value)
    {
        return $this->setFieldValue('project_hash', $value);
    }

    /**
     * Return value of is_tracking_enabled field.
     *
     * @return bool
     */
    public function getIsTrackingEnabled()
    {
        return $this->getFieldValue('is_tracking_enabled');
    }

    /**
     * Set value of is_tracking_enabled field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsTrackingEnabled($value)
    {
        return $this->setFieldValue('is_tracking_enabled', $value);
    }

    /**
     * Return value of is_billable field.
     *
     * @return bool
     */
    public function getIsBillable()
    {
        return $this->getFieldValue('is_billable');
    }

    /**
     * Set value of is_billable field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsBillable($value)
    {
        return $this->setFieldValue('is_billable', $value);
    }

    /**
     * Return value of members_can_change_billable field.
     *
     * @return bool
     */
    public function getMembersCanChangeBillable()
    {
        return $this->getFieldValue('members_can_change_billable');
    }

    /**
     * Set value of members_can_change_billable field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setMembersCanChangeBillable($value)
    {
        return $this->setFieldValue('members_can_change_billable', $value);
    }

    /**
     * Return value of is_client_reporting_enabled field.
     *
     * @return bool
     */
    public function getIsClientReportingEnabled()
    {
        return $this->getFieldValue('is_client_reporting_enabled');
    }

    /**
     * Set value of is_client_reporting_enabled field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsClientReportingEnabled($value)
    {
        return $this->setFieldValue('is_client_reporting_enabled', $value);
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
     * Return value of is_sample field.
     *
     * @return bool
     */
    public function getIsSample()
    {
        return $this->getFieldValue('is_sample');
    }

    /**
     * Set value of is_sample field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsSample($value)
    {
        return $this->setFieldValue('is_sample', $value);
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
                case 'based_on_type':
                    return parent::setFieldValue($name, (string) $value);
                case 'based_on_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'company_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'category_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'label_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'currency_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'budget_type':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'budget':
                    return parent::setFieldValue($name, (float) $value);
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'leader_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'body':
                    return parent::setFieldValue($name, (string) $value);
                case 'completed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'completed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'completed_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'completed_by_email':
                    return parent::setFieldValue($name, (string) $value);
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
                case 'last_activity_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'project_number':
                    return parent::setFieldValue($name, (int) $value);
                case 'project_hash':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_tracking_enabled':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_billable':
                    return parent::setFieldValue($name, (bool) $value);
                case 'members_can_change_billable':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_client_reporting_enabled':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'is_sample':
                    return parent::setFieldValue($name, (bool) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
