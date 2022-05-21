<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseSubtask extends ApplicationObject implements IAssignees, IComplete, IHistory, ITrash, IActivityLog, IWhoCanSeeThis, ICreatedOn, ICreatedBy, IUpdatedOn
{
    use IAssigneesImplementation;
    use ICompleteImplementation;
    use IHistoryImplementation;
    use ITrashImplementation;
    use IActivityLogImplementation;
    use IWhoCanSeeThisImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'Subtask';
    const MANAGER_NAME = 'Subtasks';

    protected string $table_name = 'subtasks';
    protected array $fields = [
        'id',
        'task_id',
        'assignee_id',
        'delegated_by_id',
        'body',
        'due_on',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'completed_on',
        'completed_by_id',
        'completed_by_name',
        'completed_by_email',
        'position',
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'fake_assignee_name',
        'fake_assignee_email',
    ];

    protected array $default_field_values = [
        'task_id' => 0,
        'assignee_id' => 0,
        'delegated_by_id' => 0,
        'position' => 0,
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
            return $underscore ? 'subtask' : 'Subtask';
        } else {
            return $underscore ? 'subtasks' : 'Subtasks';
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
     * Return value of task_id field.
     *
     * @return int
     */
    public function getTaskId()
    {
        return $this->getFieldValue('task_id');
    }

    /**
     * Set value of task_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setTaskId($value)
    {
        return $this->setFieldValue('task_id', $value);
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
                case 'task_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'assignee_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'delegated_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'body':
                    return parent::setFieldValue($name, (string) $value);
                case 'due_on':
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
