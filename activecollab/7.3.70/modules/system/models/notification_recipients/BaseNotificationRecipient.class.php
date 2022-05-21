<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseNotificationRecipient extends ApplicationObject implements IWhoCanSeeThis
{
    const MODEL_NAME = 'NotificationRecipient';
    const MANAGER_NAME = 'NotificationRecipients';

    protected string $table_name = 'notification_recipients';
    protected array $fields = [
        'id',
        'notification_id',
        'recipient_id',
        'recipient_name',
        'recipient_email',
        'read_on',
        'is_mentioned',
    ];

    protected array $default_field_values = [
        'is_mentioned' => false,
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
            return $underscore ? 'notification_recipient' : 'NotificationRecipient';
        } else {
            return $underscore ? 'notification_recipients' : 'NotificationRecipients';
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
     * Return value of notification_id field.
     *
     * @return int
     */
    public function getNotificationId()
    {
        return $this->getFieldValue('notification_id');
    }

    /**
     * Set value of notification_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setNotificationId($value)
    {
        return $this->setFieldValue('notification_id', $value);
    }

    /**
     * Return value of recipient_id field.
     *
     * @return int
     */
    public function getRecipientId()
    {
        return $this->getFieldValue('recipient_id');
    }

    /**
     * Set value of recipient_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setRecipientId($value)
    {
        return $this->setFieldValue('recipient_id', $value);
    }

    /**
     * Return value of recipient_name field.
     *
     * @return string
     */
    public function getRecipientName()
    {
        return $this->getFieldValue('recipient_name');
    }

    /**
     * Set value of recipient_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setRecipientName($value)
    {
        return $this->setFieldValue('recipient_name', $value);
    }

    /**
     * Return value of recipient_email field.
     *
     * @return string
     */
    public function getRecipientEmail()
    {
        return $this->getFieldValue('recipient_email');
    }

    /**
     * Set value of recipient_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setRecipientEmail($value)
    {
        return $this->setFieldValue('recipient_email', $value);
    }

    /**
     * Return value of read_on field.
     *
     * @return DateTimeValue
     */
    public function getReadOn()
    {
        return $this->getFieldValue('read_on');
    }

    /**
     * Set value of read_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setReadOn($value)
    {
        return $this->setFieldValue('read_on', $value);
    }

    /**
     * Return value of is_mentioned field.
     *
     * @return bool
     */
    public function getIsMentioned()
    {
        return $this->getFieldValue('is_mentioned');
    }

    /**
     * Set value of is_mentioned field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsMentioned($value)
    {
        return $this->setFieldValue('is_mentioned', $value);
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
                case 'notification_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'recipient_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'recipient_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'recipient_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'read_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'is_mentioned':
                    return parent::setFieldValue($name, (bool) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
