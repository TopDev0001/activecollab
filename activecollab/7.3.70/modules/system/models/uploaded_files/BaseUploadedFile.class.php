<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseUploadedFile extends ApplicationObject implements IFile, ICreatedOn, ICreatedBy, IAdditionalProperties
{
    use IFileImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IAdditionalPropertiesImplementation;
    const MODEL_NAME = 'UploadedFile';
    const MANAGER_NAME = 'UploadedFiles';

    protected string $table_name = 'uploaded_files';
    protected array $fields = [
        'id',
        'type',
        'name',
        'mime_type',
        'size',
        'location',
        'md5',
        'code',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'ip_address',
        'raw_additional_properties',
    ];

    protected array $default_field_values = [
        'name' => '',
        'mime_type' => 'application/octet-stream',
        'size' => 0,
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
            return $underscore ? 'uploaded_file' : 'UploadedFile';
        } else {
            return $underscore ? 'uploaded_files' : 'UploadedFiles';
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
     * Return value of code field.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getFieldValue('code');
    }

    /**
     * Set value of code field.
     *
     * @param  string $value
     * @return string
     */
    public function setCode($value)
    {
        return $this->setFieldValue('code', $value);
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
     * Return value of ip_address field.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->getFieldValue('ip_address');
    }

    /**
     * Set value of ip_address field.
     *
     * @param  string $value
     * @return string
     */
    public function setIpAddress($value)
    {
        return $this->setFieldValue('ip_address', $value);
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
                case 'type':
                    return parent::setFieldValue($name, (string) $value);
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
                case 'code':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'ip_address':
                    return parent::setFieldValue($name, (string) $value);
                case 'raw_additional_properties':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
