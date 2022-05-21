<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Job type class.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
class JobType extends BaseJobType
{
    /**
     * Returns true if there's custom hourly rate set for given project.
     *
     * @param  Company|Project $context
     * @return bool
     */
    public function hasCustomHourlyRateFor($context)
    {
        return $this->getHourlyRateFor($context) !== $this->getDefaultHourlyRate();
    }

    /**
     * Return hourly rate for given project.
     *
     * This function will first check if we have custom hourly rate set for a
     * given project. If we do, it will return custom rate, and default it no
     * custom hourly rate is set
     *
     * @param  Company|Project $context
     * @return float
     */
    public function getHourlyRateFor($context)
    {
        return JobTypes::getIdRateMapFor($context)[$this->getId()];
    }

    /**
     * Set custom hourly rate for given project.
     *
     * @param  Company|Project   $context
     * @param  float             $value
     * @throws InvalidParamError
     * @throws Exception
     */
    public function setHourlyRateFor($context, $value)
    {
        $value = is_numeric($value) ? round($value, 2) : null;

        try {
            DB::beginWork('Begin: set custom hourly rate @ ' . __CLASS__);

            if ($value) {
                DB::execute('REPLACE INTO custom_hourly_rates (parent_type, parent_id, job_type_id, hourly_rate) VALUES (?, ?, ?, ?)', get_class($context), $context->getId(), $this->getId(), $value);
            } else {
                DB::execute('DELETE FROM custom_hourly_rates WHERE parent_type = ? AND parent_id = ?', get_class($context), $context->getId());
            }

            if (DB::affectedRows()) {
                AngieApplication::cache()->removeByObject($context);

                if ($context instanceof Company && $projects = $context->getActiveProjects()) {
                    foreach ($projects as $project) {
                        $project->touchDoesntUpdateActivity();
                        $project->touch();
                        $project->touchUpdatesActivity();
                    }
                }
            }

            DB::commit('Done: set custom hourly rate @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: set custom hourly rate @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['is_default'] = $this->getIsDefault();
        $result['default_hourly_rate'] = $this->getDefaultHourlyRate();

        return $result;
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array & $result)
    {
        parent::describeSingleForFeather($result);

        $result['is_in_use'] = $this->inUse();
    }

    // ---------------------------------------------------
    //  Routing context
    // ---------------------------------------------------

    /**
     * Returns true if this job type is in use.
     *
     * @return bool
     */
    public function inUse()
    {
        return $this->getIsDefault() ||
        JobTypes::count() == 1 ||
        TimeRecords::countByJobType($this) ||
        Tasks::countByJobType($this) ||
        DB::executeFirstCell('SELECT COUNT(*) AS "row_count" FROM config_option_values WHERE name = ? AND value = ?', 'job_type_id', serialize($this->getId()));
    }

    public function getRoutingContext(): string
    {
        return 'job_type';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'job_type_id' => $this->getId(),
        ];
    }

    public function canView(User $user): bool
    {
        return $user->isOwner();
    }

    public function canArchive(User $user): bool
    {
        return $this->canEdit($user) && !$this->getIsDefault();
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner() && !$this->getIsDefault();
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            $this->validateUniquenessOf('name') or $errors->fieldValueIsRequired('name');
        } else {
            $errors->addError('Job type name is required', 'name');
        }

        if ($this->getDefaultHourlyRate() < 0.01) {
            $errors->addError('Minimum value for hourly rate is 0.01', 'default_hourly_rate');
        }

        parent::validate($errors);
    }

    /**
     * Save to database.
     */
    public function save()
    {
        $default_hourly_rate = $this->getDefaultHourlyRate();

        if ($default_hourly_rate < 0 || empty($default_hourly_rate)) {
            $this->setDefaultHourlyRate(0);
        }

        parent::save();
    }
}
