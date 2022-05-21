<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait ITrackingObjectImplementation
{
    public function registerITrackingObjectImplementation(): void
    {
        $this->addHistoryFields(
            'user_id',
            'user_name',
            'user_email',
            'record_date',
            'value',
            'billable_status'
        );

        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                unset($result['name']);

                $result['billable_status'] = $this->getBillableStatus();
                $result['value'] = $this->getValue();
                $result['record_date'] = $this->getRecordDate();
                $result['summary'] = $this->getSummary();
                $user = $this->getUser();
                $result['user_id'] = $user->getId();
                $result['user_name'] = $user instanceof AnonymousUser ? $user->getDisplayName(true) : $user->getName();
                $result['user_email'] = $user->getEmail();
            }
        );

        $this->registerEventHandler(
            'on_validate',
            function (ValidationErrors & $errors) {
                if ($this->isNew()) {
                    if (!$this->validatePresenceOf('user_id')) {
                        $errors->addError('Please select user', 'user_id');
                    }
                }

                if (!$this->validatePresenceOf('record_date')) {
                    $errors->addError('Please select record date', 'record_date');
                }

                if ($this->validatePresenceOf('value')) {
                    if ($this->getValue() <= 0) {
                        $errors->addError('Value is required', 'value');
                    }
                } else {
                    $errors->addError('Value is required', 'value');
                }
            }
        );

        $this->registerEventHandler(
            'on_set_attributes',
            function (& $attributes) {
                if (isset($attributes['user_id']) && $attributes['user_id'] != $this->getUserId()) {
                    if ($attributes['user_id']) {
                        $user = DataObjectPool::get('User', $attributes['user_id']);

                        if ($user instanceof User) {
                            $attributes['user_name'] = $user->getDisplayName();
                            $attributes['user_email'] = $user->getEmail();
                        } else {
                            $attributes['user_id'] = 0;
                        }
                    }

                    if (empty($attributes['user_id'])) {
                        if (isset($attributes['user_name'])) {
                            unset($attributes['user_name']);
                        }

                        if (isset($attributes['user_email'])) {
                            unset($attributes['user_email']);
                        }
                    }
                }
            }
        );
    }

    abstract public function addHistoryFields(string ...$field_names): void;

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return object's billable status.
     *
     * @return int
     */
    abstract public function getBillableStatus();

    /**
     * Return record value.
     *
     * @return float
     */
    abstract public function getValue();

    /**
     * Return record date.
     *
     * @return DateValue
     */
    abstract public function getRecordDate();

    /**
     * Return record summary.
     *
     * @return string
     */
    abstract public function getSummary();

    /**
     * Return parent user.
     *
     * @return IUser
     */
    public function getUser()
    {
        $user = DataObjectPool::get(User::class, $this->getUserId());

        if (empty($user)) {
            if ($this->getUserEmail()) {
                $user = new AnonymousUser($this->getUserName(), $this->getUserEmail());
            } else {
                $user = new AnonymousUser('Unknown User', 'unknown@example.com');
            }
        }

        return $user;
    }

    /**
     * Return value of user_id field.
     *
     * @return int
     */
    abstract public function getUserId();

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Return user email address.
     *
     * @return string
     */
    abstract public function getUserEmail();

    /**
     * Return user name.
     *
     * @return string
     */
    abstract public function getUserName();

    /**
     * Return true if this object has not been saved to database.
     *
     * @return bool
     */
    abstract public function isNew();

    /**
     * Validates presence of specific field.
     *
     * In case of string value is trimmed and compared with the empty string. In
     * case of any other type empty() function is used. If $min_value argument is
     * provided value will also need to be larger or equal to it
     * (validateMinValueOf validator is used)
     *
     * @param  string  $field     Field name
     * @param  mixed   $min_value
     * @param  Closure $modifier
     * @return bool
     */
    abstract public function validatePresenceOf($field, $min_value = null, $modifier = null);

    /**
     * Set parent user.
     *
     * @return IUser
     */
    public function setUser(IUser $user)
    {
        if ($user instanceof IUser) {
            $this->setUserId($user->getId());
            $this->setUserName($user->getDisplayName());
            $this->setUserEmail($user->getEmail());
        } else {
            throw new InvalidInstanceError('user', $user, 'IUser');
        }

        return $user;
    }

    /**
     * Set value of user_id field.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setUserId($value);

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Set value of user_name field.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setUserName($value);

    /**
     * Set value of user_email field.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setUserEmail($value);

    /**
     * Return invoice ID.
     *
     * @return int
     */
    public function getInvoiceId()
    {
        return AngieApplication::cache()->getByObject($this, 'invoice_id', function () {
            if ($this->getInvoiceItemId()) {
                return (int) DB::executeFirstCell('SELECT parent_id FROM invoice_items WHERE id = ?', $this->getInvoiceItemId());
            } else {
                return 0;
            }
        });
    }

    /**
     * Return parent invoice item ID.
     *
     * @return int
     */
    abstract public function getInvoiceItemId();

    /**
     * Returns true if this record is billable.
     *
     * @return bool
     */
    public function isBillable()
    {
        return $this->getBillableStatus() >= ITrackingObject::BILLABLE;
    }

    /**
     * Returns true if this record is marked as paid.
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->getBillableStatus() >= ITrackingObject::PAID;
    }

    public function touchParentOnPropertyChange(): ?array
    {
        return [
            'parent_type',
            'parent_id',
            'value',
            'job_type_id',
            'billable_status',
            'is_trashed',
        ];
    }

    public function canView(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($project = $this->getProject()) {
            if ($project->isLeader($user)) {
                return true; // Project leader can see all records
            }

            if ($user instanceof Client) {
                return $project->getIsClientReportingEnabled() && $project->isMember($user); // Member can see records if they are part of a project and client report is enabled for that project
            }

            return $project->isMember($user) && $this->getUserId() === $user->getId(); // Subcontractors and members can see only their time
        }

        return false;
    }

    /**
     * Return project that's parent of this tracking record.
     *
     * @return Project|DataObject
     */
    public function &getProject()
    {
        return DataObjectPool::get(Project::class, $this->getProjectId());
    }

    /**
     * Return project ID.
     *
     * @return int
     */
    public function getProjectId()
    {
        switch ($this->getParentType()) {
            case Project::class:
                return $this->getParentId();
            case Task::class:
                return $this->getParent()->getProjectId();
            default:
                throw new InvalidInstanceError(
                    'parent',
                    $this->getParent(),
                    [
                        Project::class,
                        Task::class,
                    ]
                );
        }
    }

    /**
     * Return parent object.
     *
     * @return Project|Task
     */
    abstract public function getParent();

    public function canEdit(User $user): bool
    {
        if ($this->isUsed()) {
            return false;
        }

        // Project manager, project leader or person with management permissions in time and expenses section
        if ($user->isPowerUser() || $this->getProject()->isLeader($user) || TrackingObjects::canManage($user, $this->getProject())) {
            return true;
        }

        // Author or person for whose account this time has been logged, editable within 30 days
        if ($this->getParent()->canView($user) && ($this->getUserId() == $user->getId() || $this->getCreatedById() == $user->getId())) {
            return ($this->getCreatedOn()->getTimestamp() + (30 * 86400)) > DateTimeValue::now()->getTimestamp();
        }

        return false;
    }

    /**
     * Return true if this particular record is used in external resources (invoice for example).
     *
     * @return bool
     */
    public function isUsed()
    {
        return $this->getBillableStatus() > ITrackingObject::BILLABLE && $this->getInvoiceItemId() > 0;
    }

    /**
     * Return ID of the user who created this record.
     *
     * @return int
     */
    abstract public function getCreatedById();

    /**
     * Return creation time stamp.
     *
     * @return DateTimeValue
     */
    abstract public function getCreatedOn();

    /**
     * Return object path.
     *
     * @return string
     */
    public function getObjectPath()
    {
        $parent = $this->getParent();

        if ($parent instanceof Project) {
            $project_id = $parent->getId();
            $is_hidden_from_clients = !$parent->getIsClientReportingEnabled();
        } else {
            if ($parent instanceof Task) {
                $project_id = $parent->getProjectId();
                $is_hidden_from_clients = $parent->getIsHiddenFromClients();
            } else {
                return '/';
            }
        }

        return 'projects/' . $project_id . '/' . ($is_hidden_from_clients ? 'hidden-from-clients' : 'visible-to-clients') . '/' . str_replace('_', '-', $this->getModelName(true)) . '/' . $this->getId();
    }

    /**
     * Return object ID.
     *
     * @return int
     */
    abstract public function getId();

    /**
     * Return value of parent_type field.
     *
     * @return string
     */
    abstract public function getParentType();

    /**
     * Return value of parent_id field.
     *
     * @return int
     */
    abstract public function getParentId();

    /**
     * Set value of value field.
     *
     * @param  float $value
     * @return float
     */
    abstract public function setValue($value);

    /**
     * Return instance for created activity log.
     *
     * @return ActivityLog
     */
    protected function getCreatedActivityLogInstance()
    {
        return new TrackingObjectCreatedActivityLog();
    }

    /**
     * Return instance for updated activity log.
     *
     * @return ActivityLog
     */
    protected function getUpdatedActivityLogInstance()
    {
        return new TrackingObjectUpdatedActivityLog();
    }

    /**
     * Return which modifications should we remember.
     *
     * @return bool
     */
    protected function whatIsWorthRemembering()
    {
        return call_user_func([$this->getModelName(), 'whatIsWorthRemembering']);
    }
    abstract public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string;
}
