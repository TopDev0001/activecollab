<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseCalendarEvent extends ApplicationObject implements ITrash, ISubscriptions, IAccessLog, IHistory, IActivityLog, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IAdditionalProperties, ICreatedOn, ICreatedBy, IUpdatedOn
{
    use ITrashImplementation;
    use ISubscriptionsImplementation;
    use IAccessLogImplementation;
    use IHistoryImplementation;
    use IActivityLogImplementation;
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    use IAdditionalPropertiesImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    const MODEL_NAME = 'CalendarEvent';
    const MANAGER_NAME = 'CalendarEvents';

    protected string $table_name = 'calendar_events';
    protected array $fields = [
        'id',
        'calendar_id',
        'name',
        'starts_on',
        'starts_on_time',
        'ends_on',
        'ends_on_time',
        'repeat_event',
        'repeat_until',
        'raw_additional_properties',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'note',
        'position',
    ];

    protected array $default_field_values = [
        'calendar_id' => 0,
        'name' => '',
        'repeat_event' => 'dont',
        'is_trashed' => false,
        'original_is_trashed' => false,
        'trashed_by_id' => 0,
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
            return $underscore ? 'calendar_event' : 'CalendarEvent';
        } else {
            return $underscore ? 'calendar_events' : 'CalendarEvents';
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
     * Return value of calendar_id field.
     *
     * @return int
     */
    public function getCalendarId()
    {
        return $this->getFieldValue('calendar_id');
    }

    /**
     * Set value of calendar_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCalendarId($value)
    {
        return $this->setFieldValue('calendar_id', $value);
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
     * Return value of starts_on field.
     *
     * @return DateValue
     */
    public function getStartsOn()
    {
        return $this->getFieldValue('starts_on');
    }

    /**
     * Set value of starts_on field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setStartsOn($value)
    {
        return $this->setFieldValue('starts_on', $value);
    }

    /**
     * Return value of starts_on_time field.
     *
     * @return DateTimeValue
     */
    public function getStartsOnTime()
    {
        return $this->getFieldValue('starts_on_time');
    }

    /**
     * Set value of starts_on_time field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setStartsOnTime($value)
    {
        return $this->setFieldValue('starts_on_time', $value);
    }

    /**
     * Return value of ends_on field.
     *
     * @return DateValue
     */
    public function getEndsOn()
    {
        return $this->getFieldValue('ends_on');
    }

    /**
     * Set value of ends_on field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setEndsOn($value)
    {
        return $this->setFieldValue('ends_on', $value);
    }

    /**
     * Return value of ends_on_time field.
     *
     * @return DateTimeValue
     */
    public function getEndsOnTime()
    {
        return $this->getFieldValue('ends_on_time');
    }

    /**
     * Set value of ends_on_time field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setEndsOnTime($value)
    {
        return $this->setFieldValue('ends_on_time', $value);
    }

    /**
     * Return value of repeat_event field.
     *
     * @return string
     */
    public function getRepeatEvent()
    {
        return $this->getFieldValue('repeat_event');
    }

    /**
     * Set value of repeat_event field.
     *
     * @param  string $value
     * @return string
     */
    public function setRepeatEvent($value)
    {
        return $this->setFieldValue('repeat_event', $value);
    }

    /**
     * Return value of repeat_until field.
     *
     * @return DateValue
     */
    public function getRepeatUntil()
    {
        return $this->getFieldValue('repeat_until');
    }

    /**
     * Set value of repeat_until field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setRepeatUntil($value)
    {
        return $this->setFieldValue('repeat_until', $value);
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
     * Return value of note field.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->getFieldValue('note');
    }

    /**
     * Set value of note field.
     *
     * @param  string $value
     * @return string
     */
    public function setNote($value)
    {
        return $this->setFieldValue('note', $value);
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
                case 'calendar_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'starts_on':
                    return parent::setFieldValue($name, dateval($value));
                case 'starts_on_time':
                    return parent::setFieldValue($name, timeval($value));
                case 'ends_on':
                    return parent::setFieldValue($name, dateval($value));
                case 'ends_on_time':
                    return parent::setFieldValue($name, timeval($value));
                case 'repeat_event':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'repeat_until':
                    return parent::setFieldValue($name, dateval($value));
                case 'raw_additional_properties':
                    return parent::setFieldValue($name, (string) $value);
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
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'original_is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'note':
                    return parent::setFieldValue($name, (string) $value);
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
