<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseTaskList extends ApplicationObject implements IHistory, ITrash, IComplete, \Angie\Search\SearchItem\SearchItemInterface, IActivityLog, IProjectElement, IWhoCanSeeThis, IInvoiceBasedOn, ICreatedOn, ICreatedBy, IUpdatedOn, IUpdatedBy
{
    use IHistoryImplementation;
    use ITrashImplementation;
    use ICompleteImplementation;
    use \Angie\Search\SearchItem\SearchItemImplementation;
    use IActivityLogImplementation;
    use IProjectElementImplementation;
    use IWhoCanSeeThisImplementation;
    use IInvoiceBasedOnTrackedDataImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IUpdatedByImplementation {
        IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation;
    }
    const MODEL_NAME = 'TaskList';
    const MANAGER_NAME = 'TaskLists';

    protected string $table_name = 'task_lists';
    protected array $fields = [
        'id',
        'project_id',
        'name',
        'start_on',
        'due_on',
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
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'position',
    ];

    protected array $default_field_values = [
        'project_id' => 0,
        'name' => '',
        'is_trashed' => false,
        'original_is_trashed' => false,
        'trashed_by_id' => 0,
        'position' => 0,
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
            return $underscore ? 'task_list' : 'TaskList';
        } else {
            return $underscore ? 'task_lists' : 'TaskLists';
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
     * Return value of start_on field.
     *
     * @return DateValue
     */
    public function getStartOn()
    {
        return $this->getFieldValue('start_on');
    }

    /**
     * Set value of start_on field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setStartOn($value)
    {
        return $this->setFieldValue('start_on', $value);
    }

    /**
     * Return value of due_on field.
     *
     * @return DateValue
     */
    public function getDueOn()
    {
        return $this->getFieldValue('due_on');
    }

    /**
     * Set value of due_on field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setDueOn($value)
    {
        return $this->setFieldValue('due_on', $value);
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
     * Return value of position field.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->getFieldValue('position');
    }

    /**
     * Set value of position field.
     *
     * @param  int $value
     * @return int
     */
    public function setPosition($value)
    {
        return $this->setFieldValue('position', $value);
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
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'start_on':
                    return parent::setFieldValue($name, dateval($value));
                case 'due_on':
                    return parent::setFieldValue($name, dateval($value));
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
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'original_is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
