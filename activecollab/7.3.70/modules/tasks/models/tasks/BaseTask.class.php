<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseTask extends ApplicationObject implements IAssignees, IComplete, IHistory, IAccessLog, ISubscriptions, IIncomingMail, IComments, IAttachments, ILabels, ITrash, \Angie\Search\SearchItem\SearchItemInterface, IActivityLog, IReminders, IHiddenFromClients, ITaskDependencies, IWhoCanSeeThis, IProjectElement, ITracking, IInvoiceBasedOn, IBody, ICreatedOn, ICreatedBy, IUpdatedOn, IUpdatedBy
{
    use IAssigneesImplementation;
    use ICompleteImplementation;
    use IHistoryImplementation;
    use IAccessLogImplementation;
    use ISubscriptionsImplementation;
    use ICommentsImplementation;
    use IAttachmentsImplementation;
    use ILabelsImplementation;
    use ITrashImplementation;
    use \Angie\Search\SearchItem\SearchItemImplementation;
    use IActivityLogImplementation;
    use IRemindersImplementation;
    use ITaskDependenciesImplementation;
    use IWhoCanSeeThisImplementation;
    use IProjectElementImplementation;
    use ITrackingImplementation;
    use IInvoiceBasedOnTrackedDataImplementation;
    use IBodyImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IUpdatedByImplementation {
        IProjectElementImplementation::canViewAccessLogs insteadof IAccessLogImplementation;
        IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation;
    }
    const MODEL_NAME = 'Task';
    const MANAGER_NAME = 'Tasks';

    protected string $table_name = 'tasks';
    protected array $fields = [
        'id',
        'project_id',
        'task_number',
        'task_list_id',
        'assignee_id',
        'delegated_by_id',
        'created_from_recurring_task_id',
        'name',
        'body',
        'body_mode',
        'is_important',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'updated_by_id',
        'updated_by_name',
        'updated_by_email',
        'start_on',
        'due_on',
        'job_type_id',
        'estimate',
        'completed_on',
        'completed_by_id',
        'completed_by_name',
        'completed_by_email',
        'position',
        'is_hidden_from_clients',
        'is_billable',
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'fake_assignee_name',
        'fake_assignee_email',
    ];

    protected array $default_field_values = [
        'project_id' => 0,
        'task_number' => 0,
        'task_list_id' => 0,
        'assignee_id' => 0,
        'delegated_by_id' => 0,
        'created_from_recurring_task_id' => 0,
        'name' => '',
        'body_mode' => 'paragraph',
        'is_important' => false,
        'job_type_id' => 0,
        'estimate' => 0.0,
        'position' => 0,
        'is_hidden_from_clients' => false,
        'is_billable' => true,
        'is_trashed' => false,
        'original_is_trashed' => false,
        'trashed_by_id' => 0,
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
            return $underscore ? 'task' : 'Task';
        } else {
            return $underscore ? 'tasks' : 'Tasks';
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
     * Return value of task_number field.
     *
     * @return int
     */
    public function getTaskNumber()
    {
        return $this->getFieldValue('task_number');
    }

    /**
     * Set value of task_number field.
     *
     * @param  int $value
     * @return int
     */
    public function setTaskNumber($value)
    {
        return $this->setFieldValue('task_number', $value);
    }

    /**
     * Return value of task_list_id field.
     *
     * @return int
     */
    public function getTaskListId()
    {
        return $this->getFieldValue('task_list_id');
    }

    /**
     * Set value of task_list_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setTaskListId($value)
    {
        return $this->setFieldValue('task_list_id', $value);
    }

    /**
     * Return value of assignee_id field.
     *
     * @return int
     */
    public function getAssigneeId()
    {
        return $this->getFieldValue('assignee_id');
    }

    /**
     * Set value of assignee_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setAssigneeId($value)
    {
        return $this->setFieldValue('assignee_id', $value);
    }

    /**
     * Return value of delegated_by_id field.
     *
     * @return int
     */
    public function getDelegatedById()
    {
        return $this->getFieldValue('delegated_by_id');
    }

    /**
     * Set value of delegated_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setDelegatedById($value)
    {
        return $this->setFieldValue('delegated_by_id', $value);
    }

    /**
     * Return value of created_from_recurring_task_id field.
     *
     * @return int
     */
    public function getCreatedFromRecurringTaskId()
    {
        return $this->getFieldValue('created_from_recurring_task_id');
    }

    /**
     * Set value of created_from_recurring_task_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCreatedFromRecurringTaskId($value)
    {
        return $this->setFieldValue('created_from_recurring_task_id', $value);
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
     * Return value of body_mode field.
     *
     * @return string
     */
    public function getBodyMode()
    {
        return $this->getFieldValue('body_mode');
    }

    /**
     * Set value of body_mode field.
     *
     * @param  string $value
     * @return string
     */
    public function setBodyMode($value)
    {
        return $this->setFieldValue('body_mode', $value);
    }

    /**
     * Return value of is_important field.
     *
     * @return bool
     */
    public function getIsImportant()
    {
        return $this->getFieldValue('is_important');
    }

    /**
     * Set value of is_important field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsImportant($value)
    {
        return $this->setFieldValue('is_important', $value);
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
     * Return value of estimate field.
     *
     * @return float
     */
    public function getEstimate()
    {
        return $this->getFieldValue('estimate');
    }

    /**
     * Set value of estimate field.
     *
     * @param  float $value
     * @return float
     */
    public function setEstimate($value)
    {
        return $this->setFieldValue('estimate', $value);
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
     * Return value of is_hidden_from_clients field.
     *
     * @return bool
     */
    public function getIsHiddenFromClients()
    {
        return $this->getFieldValue('is_hidden_from_clients');
    }

    /**
     * Set value of is_hidden_from_clients field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsHiddenFromClients($value)
    {
        return $this->setFieldValue('is_hidden_from_clients', $value);
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
     * Return value of fake_assignee_name field.
     *
     * @return string
     */
    public function getFakeAssigneeName()
    {
        return $this->getFieldValue('fake_assignee_name');
    }

    /**
     * Set value of fake_assignee_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setFakeAssigneeName($value)
    {
        return $this->setFieldValue('fake_assignee_name', $value);
    }

    /**
     * Return value of fake_assignee_email field.
     *
     * @return string
     */
    public function getFakeAssigneeEmail()
    {
        return $this->getFieldValue('fake_assignee_email');
    }

    /**
     * Set value of fake_assignee_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setFakeAssigneeEmail($value)
    {
        return $this->setFieldValue('fake_assignee_email', $value);
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
                case 'task_number':
                    return parent::setFieldValue($name, (int) $value);
                case 'task_list_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'assignee_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'delegated_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_from_recurring_task_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'body':
                    return parent::setFieldValue($name, (string) $value);
                case 'body_mode':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'is_important':
                    return parent::setFieldValue($name, (bool) $value);
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
                case 'start_on':
                    return parent::setFieldValue($name, dateval($value));
                case 'due_on':
                    return parent::setFieldValue($name, dateval($value));
                case 'job_type_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'estimate':
                    return parent::setFieldValue($name, (float) $value);
                case 'completed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'completed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'completed_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'completed_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
                case 'is_hidden_from_clients':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_billable':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'original_is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'fake_assignee_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'fake_assignee_email':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
