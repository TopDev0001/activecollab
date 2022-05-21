<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseAttachment extends ApplicationObject implements ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IChild, IFile, ICreatedOn, ICreatedBy, IAdditionalProperties
{
    use IChildImplementation;
    use IFileImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IAdditionalPropertiesImplementation;
    const MODEL_NAME = 'Attachment';
    const MANAGER_NAME = 'Attachments';

    protected string $table_name = 'attachments';
    protected array $fields = [
        'id',
        'type',
        'parent_type',
        'parent_id',
        'name',
        'mime_type',
        'size',
        'location',
        'md5',
        'disposition',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'raw_additional_properties',
        'search_content',
        'project_id',
        'is_hidden_from_clients',
    ];

    protected array $default_field_values = [
        'name' => '',
        'mime_type' => 'application/octet-stream',
        'size' => 0,
        'disposition' => 'attachment',
        'project_id' => 0,
        'is_hidden_from_clients' => false,
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
            return $underscore ? 'attachment' : 'Attachment';
        } else {
            return $underscore ? 'attachments' : 'Attachments';
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
     * Return value of mime_type field.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->getFieldValue('mime_type');
    }

    /**
     * Set value of mime_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setMimeType($value)
    {
        return $this->setFieldValue('mime_type', $value);
    }

    /**
     * Return value of size field.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getFieldValue('size');
    }

    /**
     * Set value of size field.
     *
     * @param  int $value
     * @return int
     */
    public function setSize($value)
    {
        return $this->setFieldValue('size', $value);
    }

    /**
     * Return value of location field.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->getFieldValue('location');
    }

    /**
     * Set value of location field.
     *
     * @param  string $value
     * @return string
     */
    public function setLocation($value)
    {
        return $this->setFieldValue('location', $value);
    }

    /**
     * Return value of md5 field.
     *
     * @return string
     */
    public function getMd5()
    {
        return $this->getFieldValue('md5');
    }

    /**
     * Set value of md5 field.
     *
     * @param  string $value
     * @return string
     */
    public function setMd5($value)
    {
        return $this->setFieldValue('md5', $value);
    }

    /**
     * Return value of disposition field.
     *
     * @return string
     */
    public function getDisposition()
    {
        return $this->getFieldValue('disposition');
    }

    /**
     * Set value of disposition field.
     *
     * @param  string $value
     * @return string
     */
    public function setDisposition($value)
    {
        return $this->setFieldValue('disposition', $value);
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
     * Return value of search_content field.
     *
     * @return string
     */
    public function getSearchContent()
    {
        return $this->getFieldValue('search_content');
    }

    /**
     * Set value of search_content field.
     *
     * @param  string $value
     * @return string
     */
    public function setSearchContent($value)
    {
        return $this->setFieldValue('search_content', $value);
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
                case 'type':
                    return parent::setFieldValue($name, (string) $value);
                case 'parent_type':
                    return parent::setFieldValue($name, (string) $value);
                case 'parent_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'mime_type':
                    return parent::setFieldValue($name, (string) $value);
                case 'size':
                    return parent::setFieldValue($name, (int) $value);
                case 'location':
                    return parent::setFieldValue($name, (string) $value);
                case 'md5':
                    return parent::setFieldValue($name, (string) $value);
                case 'disposition':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'raw_additional_properties':
                    return parent::setFieldValue($name, (string) $value);
                case 'search_content':
                    return parent::setFieldValue($name, (string) $value);
                case 'project_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'is_hidden_from_clients':
                    return parent::setFieldValue($name, (bool) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
