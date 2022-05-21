<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseUserWorkspace extends ApplicationObject implements IUpdatedOn
{
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'UserWorkspace';
    const MANAGER_NAME = 'UserWorkspaces';

    protected string $table_name = 'user_workspaces';
    protected array $fields = [
        'id',
        'user_id',
        'shepherd_account_id',
        'shepherd_account_type',
        'shepherd_account_url',
        'name',
        'is_shown_in_launcher',
        'is_owner',
        'position',
        'updated_on',
    ];

    protected array $default_field_values = [
        'user_id' => 0,
        'shepherd_account_id' => 0,
        'name' => '',
        'is_shown_in_launcher' => true,
        'is_owner' => true,
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
            return $underscore ? 'user_workspace' : 'UserWorkspace';
        } else {
            return $underscore ? 'user_workspaces' : 'UserWorkspaces';
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
     * Return value of shepherd_account_id field.
     *
     * @return int
     */
    public function getShepherdAccountId()
    {
        return $this->getFieldValue('shepherd_account_id');
    }

    /**
     * Set value of shepherd_account_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setShepherdAccountId($value)
    {
        return $this->setFieldValue('shepherd_account_id', $value);
    }

    /**
     * Return value of shepherd_account_type field.
     *
     * @return string
     */
    public function getShepherdAccountType()
    {
        return $this->getFieldValue('shepherd_account_type');
    }

    /**
     * Set value of shepherd_account_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setShepherdAccountType($value)
    {
        return $this->setFieldValue('shepherd_account_type', $value);
    }

    /**
     * Return value of shepherd_account_url field.
     *
     * @return string
     */
    public function getShepherdAccountUrl()
    {
        return $this->getFieldValue('shepherd_account_url');
    }

    /**
     * Set value of shepherd_account_url field.
     *
     * @param  string $value
     * @return string
     */
    public function setShepherdAccountUrl($value)
    {
        return $this->setFieldValue('shepherd_account_url', $value);
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
     * Return value of is_shown_in_launcher field.
     *
     * @return bool
     */
    public function getIsShownInLauncher()
    {
        return $this->getFieldValue('is_shown_in_launcher');
    }

    /**
     * Set value of is_shown_in_launcher field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsShownInLauncher($value)
    {
        return $this->setFieldValue('is_shown_in_launcher', $value);
    }

    /**
     * Return value of is_owner field.
     *
     * @return bool
     */
    public function getIsOwner()
    {
        return $this->getFieldValue('is_owner');
    }

    /**
     * Set value of is_owner field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsOwner($value)
    {
        return $this->setFieldValue('is_owner', $value);
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
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'shepherd_account_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'shepherd_account_type':
                    return parent::setFieldValue($name, (string) $value);
                case 'shepherd_account_url':
                    return parent::setFieldValue($name, (string) $value);
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_shown_in_launcher':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_owner':
                    return parent::setFieldValue($name, (bool) $value);
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
