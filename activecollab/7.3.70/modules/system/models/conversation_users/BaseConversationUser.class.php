<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseConversationUser extends ApplicationObject implements IWhoCanSeeThis, ICreatedOn, IUpdatedOn
{
    use IWhoCanSeeThisImplementation;
    use ICreatedOnImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'ConversationUser';
    const MANAGER_NAME = 'ConversationUsers';

    protected string $table_name = 'conversation_users';
    protected array $fields = [
        'id',
        'conversation_id',
        'user_id',
        'is_admin',
        'is_muted',
        'is_original_muted',
        'new_messages_since',
        'created_on',
        'updated_on',
    ];

    protected array $default_field_values = [
        'conversation_id' => 0,
        'user_id' => 0,
        'is_admin' => false,
        'is_muted' => false,
        'is_original_muted' => false,
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
            return $underscore ? 'conversation_user' : 'ConversationUser';
        } else {
            return $underscore ? 'conversation_users' : 'ConversationUsers';
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
     * Return value of is_admin field.
     *
     * @return bool
     */
    public function getIsAdmin()
    {
        return $this->getFieldValue('is_admin');
    }

    /**
     * Set value of is_admin field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsAdmin($value)
    {
        return $this->setFieldValue('is_admin', $value);
    }

    /**
     * Return value of is_muted field.
     *
     * @return bool
     */
    public function getIsMuted()
    {
        return $this->getFieldValue('is_muted');
    }

    /**
     * Set value of is_muted field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsMuted($value)
    {
        return $this->setFieldValue('is_muted', $value);
    }

    /**
     * Return value of is_original_muted field.
     *
     * @return bool
     */
    public function getIsOriginalMuted()
    {
        return $this->getFieldValue('is_original_muted');
    }

    /**
     * Set value of is_original_muted field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsOriginalMuted($value)
    {
        return $this->setFieldValue('is_original_muted', $value);
    }

    /**
     * Return value of new_messages_since field.
     *
     * @return DateTimeValue
     */
    public function getNewMessagesSince()
    {
        return $this->getFieldValue('new_messages_since');
    }

    /**
     * Set value of new_messages_since field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setNewMessagesSince($value)
    {
        return $this->setFieldValue('new_messages_since', $value);
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
                case 'conversation_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'user_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'is_admin':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_muted':
                    return parent::setFieldValue($name, (bool) $value);
                case 'is_original_muted':
                    return parent::setFieldValue($name, (bool) $value);
                case 'new_messages_since':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
