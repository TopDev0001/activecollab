<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseCompany extends ApplicationObject implements IMembers, \Angie\Search\SearchItem\SearchItemInterface, IHistory, ITrash, IArchive, IActivityLog, IHourlyRates, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, ICreatedOn, ICreatedBy, IUpdatedOn, IUpdatedBy
{
    use IMembersImplementation;
    use \Angie\Search\SearchItem\SearchItemImplementation;
    use IHistoryImplementation;
    use ITrashImplementation;
    use IArchiveImplementation;
    use IActivityLogImplementation;
    use IHourlyRatesImplementation;
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IUpdatedByImplementation;
    const MODEL_NAME = 'Company';
    const MANAGER_NAME = 'Companies';

    protected string $table_name = 'companies';
    protected array $fields = [
        'id',
        'name',
        'address',
        'homepage_url',
        'phone',
        'note',
        'currency_id',
        'tax_id',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'updated_by_id',
        'updated_by_name',
        'updated_by_email',
        'is_archived',
        'archived_on',
        'is_trashed',
        'trashed_on',
        'trashed_by_id',
        'is_owner',
    ];

    protected array $default_field_values = [
        'name' => '',
        'is_archived' => false,
        'is_trashed' => false,
        'trashed_by_id' => 0,
        'is_owner' => false,
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
            return $underscore ? 'company' : 'Company';
        } else {
            return $underscore ? 'companies' : 'Companies';
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
     * Return value of address field.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->getFieldValue('address');
    }

    /**
     * Set value of address field.
     *
     * @param  string $value
     * @return string
     */
    public function setAddress($value)
    {
        return $this->setFieldValue('address', $value);
    }

    /**
     * Return value of homepage_url field.
     *
     * @return string
     */
    public function getHomepageUrl()
    {
        return $this->getFieldValue('homepage_url');
    }

    /**
     * Set value of homepage_url field.
     *
     * @param  string $value
     * @return string
     */
    public function setHomepageUrl($value)
    {
        return $this->setFieldValue('homepage_url', $value);
    }

    /**
     * Return value of phone field.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->getFieldValue('phone');
    }

    /**
     * Set value of phone field.
     *
     * @param  string $value
     * @return string
     */
    public function setPhone($value)
    {
        return $this->setFieldValue('phone', $value);
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
     * Return value of currency_id field.
     *
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->getFieldValue('currency_id');
    }

    /**
     * Set value of currency_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCurrencyId($value)
    {
        return $this->setFieldValue('currency_id', $value);
    }

    /**
     * Return value of tax_id field.
     *
     * @return string
     */
    public function getTaxId()
    {
        return $this->getFieldValue('tax_id');
    }

    /**
     * Set value of tax_id field.
     *
     * @param  string $value
     * @return string
     */
    public function setTaxId($value)
    {
        return $this->setFieldValue('tax_id', $value);
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
     * Return value of is_archived field.
     *
     * @return bool
     */
    public function getIsArchived()
    {
        return $this->getFieldValue('is_archived');
    }

    /**
     * Set value of is_archived field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsArchived($value)
    {
        return $this->setFieldValue('is_archived', $value);
    }

    /**
     * Return value of archived_on field.
     *
     * @return DateTimeValue
     */
    public function getArchivedOn()
    {
        return $this->getFieldValue('archived_on');
    }

    /**
     * Set value of archived_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setArchivedOn($value)
    {
        return $this->setFieldValue('archived_on', $value);
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
                case 'address':
                    return parent::setFieldValue($name, (string) $value);
                case 'homepage_url':
                    return parent::setFieldValue($name, (string) $value);
                case 'phone':
                    return parent::setFieldValue($name, (string) $value);
                case 'note':
                    return parent::setFieldValue($name, (string) $value);
                case 'currency_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'tax_id':
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
                case 'updated_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'updated_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'updated_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_archived':
                    return parent::setFieldValue($name, (bool) $value);
                case 'archived_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'is_owner':
                    return parent::setFieldValue($name, (bool) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
