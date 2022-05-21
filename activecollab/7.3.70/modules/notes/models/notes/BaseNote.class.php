<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseNote extends ApplicationObject implements \Angie\Search\SearchItem\SearchItemInterface, ISubscriptions, IIncomingMail, IComments, IAttachments, IHistory, IAccessLog, ITrash, IActivityLog, IHiddenFromClients, IProjectElement, IWhoCanSeeThis, IBody, ICreatedOn, ICreatedBy, IUpdatedOn, IUpdatedBy, IAdditionalProperties
{
    use \Angie\Search\SearchItem\SearchItemImplementation;
    use ISubscriptionsImplementation;
    use ICommentsImplementation;
    use IAttachmentsImplementation;
    use IHistoryImplementation;
    use IAccessLogImplementation;
    use ITrashImplementation;
    use IActivityLogImplementation;
    use IProjectElementImplementation;
    use IWhoCanSeeThisImplementation;
    use IBodyImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IUpdatedByImplementation;
    use IAdditionalPropertiesImplementation {
        IProjectElementImplementation::canViewAccessLogs insteadof IAccessLogImplementation;
        IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation;
    }
    const MODEL_NAME = 'Note';
    const MANAGER_NAME = 'Notes';

    protected string $table_name = 'notes';
    protected array $fields = [
        'id',
        'name',
        'project_id',
        'note_group_id',
        'body',
        'body_mode',
        'position',
        'is_hidden_from_clients',
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'updated_by_id',
        'updated_by_name',
        'updated_by_email',
        'raw_additional_properties',
    ];

    protected array $default_field_values = [
        'name' => '',
        'project_id' => 0,
        'note_group_id' => 0,
        'body_mode' => 'paragraph',
        'position' => 0,
        'is_hidden_from_clients' => false,
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
            return $underscore ? 'note' : 'Note';
        } else {
            return $underscore ? 'notes' : 'Notes';
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
     * Return value of note_group_id field.
     *
     * @return int
     */
    public function getNoteGroupId()
    {
        return $this->getFieldValue('note_group_id');
    }

    /**
     * Set value of note_group_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setNoteGroupId($value)
    {
        return $this->setFieldValue('note_group_id', $value);
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
     * Return value of raw_additional_properties field.
     *
     * @return string
     */
    public function getRawAdditionalProperties()
    {
        return $this->getFieldValue('raw_additional_properties');
    }

    /**
     * Set value of raw_additional_properties field.
     *
     * @param  string $value
     * @return string
     */
    public function setRawAdditionalProperties($value)
    {
        return $this->setFieldValue('raw_additional_properties', $value);
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
                case 'project_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'note_group_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'body':
                    return parent::setFieldValue($name, (string) $value);
                case 'body_mode':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
                case 'is_hidden_from_clients':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'original_is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
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
                case 'raw_additional_properties':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
