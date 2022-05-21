<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseUser extends ApplicationObject implements IArchive, ITrash, IHistory, IActivityLog, \Angie\Search\SearchItem\SearchItemInterface, ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, ICreatedOn, ICreatedBy, IUpdatedOn, IAdditionalProperties
{
    use IArchiveImplementation;
    use ITrashImplementation;
    use IHistoryImplementation;
    use IActivityLogImplementation;
    use \Angie\Search\SearchItem\SearchItemImplementation;
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation;
    use IAdditionalPropertiesImplementation;
    const MODEL_NAME = 'User';
    const MANAGER_NAME = 'Users';

    protected string $table_name = 'users';
    protected array $fields = [
        'id',
        'type',
        'company_id',
        'language_id',
        'first_name',
        'last_name',
        'title',
        'email',
        'phone',
        'im_type',
        'im_handle',
        'password',
        'password_hashed_with',
        'password_reset_key',
        'password_reset_on',
        'avatar_location',
        'daily_capacity',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'is_archived',
        'original_is_archived',
        'archived_on',
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'raw_additional_properties',
        'is_eligible_for_covid_discount',
        'first_login_on',
        'paid_on',
        'policy_version',
        'policy_accepted_on',
    ];

    protected array $default_field_values = [
        'company_id' => 0,
        'language_id' => 0,
        'email' => '',
        'password' => '',
        'password_hashed_with' => 'php',
        'is_archived' => false,
        'original_is_archived' => false,
        'is_trashed' => false,
        'original_is_trashed' => false,
        'trashed_by_id' => 0,
        'is_eligible_for_covid_discount' => false,
        'policy_version' => 'january_2019',
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
            return $underscore ? 'user' : 'User';
        } else {
            return $underscore ? 'users' : 'Users';
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
     * Return value of company_id field.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->getFieldValue('company_id');
    }

    /**
     * Set value of company_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCompanyId($value)
    {
        return $this->setFieldValue('company_id', $value);
    }

    /**
     * Return value of language_id field.
     *
     * @return int
     */
    public function getLanguageId()
    {
        return $this->getFieldValue('language_id');
    }

    /**
     * Set value of language_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setLanguageId($value)
    {
        return $this->setFieldValue('language_id', $value);
    }

    /**
     * Return value of first_name field.
     *
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->getFieldValue('first_name');
    }

    /**
     * Set value of first_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setFirstName($value)
    {
        return $this->setFieldValue('first_name', $value);
    }

    /**
     * Return value of last_name field.
     *
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->getFieldValue('last_name');
    }

    /**
     * Set value of last_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setLastName($value)
    {
        return $this->setFieldValue('last_name', $value);
    }

    /**
     * Return value of title field.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getFieldValue('title');
    }

    /**
     * Set value of title field.
     *
     * @param  string $value
     * @return string
     */
    public function setTitle($value)
    {
        return $this->setFieldValue('title', $value);
    }

    /**
     * Return value of email field.
     */
    public function getEmail(): string
    {
        return $this->getFieldValue('email');
    }

    /**
     * Set value of email field.
     *
     * @param  string $value
     * @return string
     */
    public function setEmail($value)
    {
        return $this->setFieldValue('email', $value);
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
     * Return value of im_type field.
     *
     * @return string
     */
    public function getImType()
    {
        return $this->getFieldValue('im_type');
    }

    /**
     * Set value of im_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setImType($value)
    {
        return $this->setFieldValue('im_type', $value);
    }

    /**
     * Return value of im_handle field.
     *
     * @return string
     */
    public function getImHandle()
    {
        return $this->getFieldValue('im_handle');
    }

    /**
     * Set value of im_handle field.
     *
     * @param  string $value
     * @return string
     */
    public function setImHandle($value)
    {
        return $this->setFieldValue('im_handle', $value);
    }

    /**
     * Return value of password field.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->getFieldValue('password');
    }

    /**
     * Set value of password field.
     *
     * @param  string $value
     * @return string
     */
    public function setPassword($value)
    {
        return $this->setFieldValue('password', $value);
    }

    /**
     * Return value of password_hashed_with field.
     *
     * @return string
     */
    public function getPasswordHashedWith()
    {
        return $this->getFieldValue('password_hashed_with');
    }

    /**
     * Set value of password_hashed_with field.
     *
     * @param  string $value
     * @return string
     */
    public function setPasswordHashedWith($value)
    {
        return $this->setFieldValue('password_hashed_with', $value);
    }

    /**
     * Return value of password_reset_key field.
     *
     * @return string
     */
    public function getPasswordResetKey()
    {
        return $this->getFieldValue('password_reset_key');
    }

    /**
     * Set value of password_reset_key field.
     *
     * @param  string $value
     * @return string
     */
    public function setPasswordResetKey($value)
    {
        return $this->setFieldValue('password_reset_key', $value);
    }

    /**
     * Return value of password_reset_on field.
     *
     * @return DateTimeValue
     */
    public function getPasswordResetOn()
    {
        return $this->getFieldValue('password_reset_on');
    }

    /**
     * Set value of password_reset_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setPasswordResetOn($value)
    {
        return $this->setFieldValue('password_reset_on', $value);
    }

    /**
     * Return value of avatar_location field.
     *
     * @return string
     */
    public function getAvatarLocation()
    {
        return $this->getFieldValue('avatar_location');
    }

    /**
     * Set value of avatar_location field.
     *
     * @param  string $value
     * @return string
     */
    public function setAvatarLocation($value)
    {
        return $this->setFieldValue('avatar_location', $value);
    }

    /**
     * Return value of daily_capacity field.
     *
     * @return float
     */
    public function getDailyCapacity()
    {
        return $this->getFieldValue('daily_capacity');
    }

    /**
     * Set value of daily_capacity field.
     *
     * @param  float $value
     * @return float
     */
    public function setDailyCapacity($value)
    {
        return $this->setFieldValue('daily_capacity', $value);
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
     * Return value of original_is_archived field.
     *
     * @return bool
     */
    public function getOriginalIsArchived()
    {
        return $this->getFieldValue('original_is_archived');
    }

    /**
     * Set value of original_is_archived field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setOriginalIsArchived($value)
    {
        return $this->setFieldValue('original_is_archived', $value);
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
     * Return value of is_eligible_for_covid_discount field.
     *
     * @return bool
     */
    public function getIsEligibleForCovidDiscount()
    {
        return $this->getFieldValue('is_eligible_for_covid_discount');
    }

    /**
     * Set value of is_eligible_for_covid_discount field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsEligibleForCovidDiscount($value)
    {
        return $this->setFieldValue('is_eligible_for_covid_discount', $value);
    }

    /**
     * Return value of first_login_on field.
     *
     * @return DateTimeValue
     */
    public function getFirstLoginOn()
    {
        return $this->getFieldValue('first_login_on');
    }

    /**
     * Set value of first_login_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setFirstLoginOn($value)
    {
        return $this->setFieldValue('first_login_on', $value);
    }

    /**
     * Return value of paid_on field.
     *
     * @return DateTimeValue
     */
    public function getPaidOn()
    {
        return $this->getFieldValue('paid_on');
    }

    /**
     * Set value of paid_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setPaidOn($value)
    {
        return $this->setFieldValue('paid_on', $value);
    }

    /**
     * Return value of policy_version field.
     *
     * @return string
     */
    public function getPolicyVersion()
    {
        return $this->getFieldValue('policy_version');
    }

    /**
     * Set value of policy_version field.
     *
     * @param  string $value
     * @return string
     */
    public function setPolicyVersion($value)
    {
        return $this->setFieldValue('policy_version', $value);
    }

    /**
     * Return value of policy_accepted_on field.
     *
     * @return DateTimeValue
     */
    public function getPolicyAcceptedOn()
    {
        return $this->getFieldValue('policy_accepted_on');
    }

    /**
     * Set value of policy_accepted_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setPolicyAcceptedOn($value)
    {
        return $this->setFieldValue('policy_accepted_on', $value);
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
                case 'company_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'language_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'first_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'last_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'title':
                    return parent::setFieldValue($name, (string) $value);
                case 'email':
                    return parent::setFieldValue($name, (string) $value);
                case 'phone':
                    return parent::setFieldValue($name, (string) $value);
                case 'im_type':
                    return parent::setFieldValue($name, (string) $value);
                case 'im_handle':
                    return parent::setFieldValue($name, (string) $value);
                case 'password':
                    return parent::setFieldValue($name, (string) $value);
                case 'password_hashed_with':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'password_reset_key':
                    return parent::setFieldValue($name, (string) $value);
                case 'password_reset_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'avatar_location':
                    return parent::setFieldValue($name, (string) $value);
                case 'daily_capacity':
                    return parent::setFieldValue($name, (float) $value);
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
                case 'is_archived':
                    return parent::setFieldValue($name, (bool) $value);
                case 'original_is_archived':
                    return parent::setFieldValue($name, (bool) $value);
                case 'archived_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'original_is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'raw_additional_properties':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_eligible_for_covid_discount':
                    return parent::setFieldValue($name, (bool) $value);
                case 'first_login_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'paid_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'policy_version':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'policy_accepted_on':
                    return parent::setFieldValue($name, datetimeval($value));
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
