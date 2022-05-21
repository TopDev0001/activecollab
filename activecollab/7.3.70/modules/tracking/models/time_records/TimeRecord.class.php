<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\TimeRecordEvents\TimeRecordMoveToTrashEvent;
use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\TimeRecordEvents\TimeRecordUpdatedEvent;
use Angie\Globalization;

class TimeRecord extends BaseTimeRecord implements RoutingContextInterface
{
    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'job_type_id',
            ],
        );
    }

    public function isParentOptional(): bool
    {
        return false;
    }

    public function getVerboseType(bool $lowercase = false, Language $language = null): string
    {
        return $lowercase
            ? lang('time record', null, true, $language)
            : lang('Time Record', null, true, $language);
    }

    /**
     * Set job type for a given time record.
     */
    public function setJobType(JobType $job_type)
    {
        $this->setJobTypeId($job_type->getId());
    }

    /**
     * Return name string.
     *
     * @param  bool   $with_value
     * @return string
     */
    public function getName($with_value = false)
    {
        $user = $this->getUser();
        $value = $this->getValue();

        if ($with_value) {
            $value_job = $this->getJobType() instanceof JobType ? $this->getFormatedValue($value * $this->getJobType()->getHourlyRateFor($this->getProject())) : 0;

            return $value == 1 ?
                lang(':value hour of :job (:costs)', ['value' => $value, 'job' => $this->getJobTypeName(), 'costs' => $value_job]) :
                lang(':value hours of :job (:costs)', ['value' => $value, 'job' => $this->getJobTypeName(), 'costs' => $value_job]);
        } else {
            if ($user instanceof User) {
                return $value == 1 ?
                    lang(':value hour of :job by :name', ['value' => $value, 'job' => $this->getJobTypeName(), 'name' => $user->getDisplayName(true)]) :
                    lang(':value hours of :job by :name', ['value' => $value, 'job' => $this->getJobTypeName(), 'name' => $user->getDisplayName(true)]);
            } else {
                return $value == 1 ?
                    lang(':value hour of :job', ['value' => $value, 'job' => $this->getJobTypeName()]) :
                    lang(':value hours of :job', ['value' => $value, 'job' => $this->getJobTypeName()]);
            }
        }
    }

    /**
     * Return time record job type.
     *
     * @return JobType
     */
    public function getJobType()
    {
        return DataObjectPool::get(JobType::class, $this->getJobTypeId());
    }

    /**
     * Return value formated with currency.
     *
     * @param  float  $value
     * @return string
     */
    public function getFormatedValue($value)
    {
        return Globalization::formatNumber($value);
    }

    /**
     * Return name of the job type.
     *
     * @return string
     */
    public function getJobTypeName()
    {
        return $this->getJobType() instanceof JobType ? $this->getJobType()->getName() : JobTypes::getNameById($this->getJobTypeId());
    }

    /**
     * Return Currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->getProject() instanceof Project && $this->getProject()->getCurrency() instanceof Currency ? $this->getProject()->getCurrency() : null;
    }

    /**
     * Convert time to money.
     *
     * @return float
     */
    public function calculateExpense()
    {
        return $this->getValue() * $this->getJobType()->getHourlyRateFor($this->getProject());
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['job_type_id'] = $this->getJobTypeId();
        $result['source'] = $this->getSource();
        $result['original_is_trashed'] = $this->getOriginalIsTrashed();

        return $result;
    }

    // ---------------------------------------------------
    //  Interface implementations
    // ---------------------------------------------------

    public function getRoutingContext(): string
    {
        return 'time_record';
    }

    public function getRoutingContextParams(): array
    {
        $parent = $this->getParent();

        if ($parent instanceof Task) {
            $project = $parent->getProject();
        } else {
            $project = $parent;
        }

        return [
            'project_id' => $project->getId(),
            'time_record_id' => $this->getId(),
        ];
    }

    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Set value of specific field.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return mixed
     */
    public function setFieldValue($name, $value)
    {
        if ($name === 'value') {
            if (strpos($value, ':') !== false) {
                $value = time_to_float($value);
            }

            if ($value < 0.01) {
                $value = 0.01;
            }
        }

        return parent::setFieldValue($name, $value);
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors &$errors)
    {
        if ($this->validatePresenceOf('job_type_id')) {
            if ($this->isNew()) {
                if ($job_type = $this->getJobType()) {
                    if ($job_type->getIsArchived()) {
                        $errors->addError('Archived job types cannot be used for new time records', 'job_type_id');
                    }
                } else {
                    $errors->fieldValueIsRequired('job_type_id');
                }
            }
        } else {
            $errors->fieldValueIsRequired('job_type_id');
        }

        parent::validate($errors);
    }

    public function moveToTrash(User $by = null, $bulk = false)
    {
        parent::moveToTrash($by, $bulk);

        AngieApplication::eventsDispatcher()->trigger(new TimeRecordUpdatedEvent($this));
        DataObjectPool::announce(new TimeRecordMoveToTrashEvent($this));
    }

    public function restoreFromTrash($bulk = false)
    {
        parent::restoreFromTrash($bulk);

        AngieApplication::eventsDispatcher()->trigger(new TimeRecordUpdatedEvent($this));
    }
}
