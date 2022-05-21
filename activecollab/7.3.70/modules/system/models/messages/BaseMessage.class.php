<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseMessage extends ApplicationObject implements ActiveCollab\Module\System\Model\Message\MessageInterface, IWhoCanSeeThis, IBody, ICreatedOn, ICreatedBy, IUpdatedOn, IAdditionalProperties
{
    use IWhoCanSeeThisImplementation;
    use IBodyImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IAdditionalPropertiesImplementation;
    const MODEL_NAME = 'Message';
    const MANAGER_NAME = 'Messages';

    protected string $table_name = 'messages';
    protected array $fields = [
        'id',
        'order_id',
        'type',
        'conversation_id',
        'body',
        'changed_on',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'raw_additional_properties',
    ];

    protected array $default_field_values = [
        'order_id' => 0,
        'conversation_id' => 0,
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
            return $underscore ? 'message' : 'Message';
        } else {
            return $underscore ? 'messages' : 'Messages';
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
     * Return value of order_id field.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->getFieldValue('order_id');
    }

    /**
     * Set value of order_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setOrderId($value)
    {
        return $this->setFieldValue('order_id', $value);
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
     * Return value of conversation_id field.
     *
     * @return int
     */
    public function getConversationId()
    {
        return $this->getFieldValue('conversation_id');
    }

    /**
     * Set value of conversation_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setConversationId($value)
    {
        return $this->setFieldValue('conversation_id', $value);
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
     * Return value of changed_on field.
     *
     * @return DateTimeValue
     */
    public function getChangedOn()
    {
        return $this->getFieldValue('changed_on');
    }

    /**
     * Set value of changed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setChangedOn($value)
    {
        return $this->setFieldValue('changed_on', $value);
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
                case 'order_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'type':
                    return parent::setFieldValue($name, (string) $value);
                case 'conversation_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'body':
                    return parent::setFieldValue($name, (string) $value);
                case 'changed_on':
                    return parent::setFieldValue($name, datetimeval($value));
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
                case 'raw_additional_properties':
                    return parent::setFieldValue($name, (string) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
