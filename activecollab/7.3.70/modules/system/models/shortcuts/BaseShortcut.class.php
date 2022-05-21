<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseShortcut extends ApplicationObject implements IUpdatedOn, ICreatedOn, ICreatedBy
{
    use IUpdatedOnImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    public const MODEL_NAME = 'Shortcut';
    public const MANAGER_NAME = 'Shortcuts';

    protected string $table_name = 'shortcuts';
    protected array $fields = [
        'id',
        'name',
        'url',
        'relative_url',
        'icon',
        'position',
        'updated_on',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
    ];

    protected array $default_field_values = [
        'icon' => 'insert_link',
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
            return $underscore ? 'shortcut' : 'Shortcut';
        } else {
            return $underscore ? 'shortcuts' : 'Shortcuts';
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
     * Return value of url field.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->getFieldValue('url');
    }

    /**
     * Set value of url field.
     *
     * @param  string $value
     * @return string
     */
    public function setUrl($value)
    {
        return $this->setFieldValue('url', $value);
    }

    /**
     * Return value of relative_url field.
     *
     * @return string
     */
    public function getRelativeUrl()
    {
        return $this->getFieldValue('relative_url');
    }

    /**
     * Set value of relative_url field.
     *
     * @param  string $value
     * @return string
     */
    public function setRelativeUrl($value)
    {
        return $this->setFieldValue('relative_url', $value);
    }

    /**
     * Return value of icon field.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->getFieldValue('icon');
    }

    /**
     * Set value of icon field.
     *
     * @param  string $value
     * @return string
     */
    public function setIcon($value)
    {
        return $this->setFieldValue('icon', $value);
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
                case 'url':
                    return parent::setFieldValue($name, (string) $value);
                case 'relative_url':
                    return parent::setFieldValue($name, (string) $value);
                case 'icon':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_by_email':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
