<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

abstract class FwDayOff extends BaseDayOff implements RoutingContextInterface
{
    /**
     * * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['start_date'] = $this->getStartDate();
        $result['end_date'] = $this->getEndDate();
        $result['is_multi_day'] = $this->isMultiDay();
        $result['repeat_yearly'] = $this->getRepeatYearly();

        return $result;
    }

    /**
     * Return true if this day off is multi-day.
     *
     * @return bool
     */
    public function isMultiDay()
    {
        return $this->getStartDate() instanceof DateValue && $this->getEndDate() instanceof DateValue && !$this->getStartDate()->isSameDay($this->getEndDate());
    }

    public function canView(User $user): bool
    {
        return $user->isOwner();
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner();
    }

    // ---------------------------------------------------
    //  Routing Context
    // ---------------------------------------------------

    public function getRoutingContext(): string
    {
        return 'day_off';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'day_off_id' => $this->getId(),
        ];
    }

    // ---------------------------------------------------
    //  Validation
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            if (!$this->validateUniquenessOf('name', 'start_date', 'end_date')) {
                $errors->addError('Event already specified for given date', 'name');
            }
        } else {
            $errors->addError('Name is required', 'name');
        }

        $start_date_present = $this->validatePresenceOf('start_date');
        $end_date_present = $this->validatePresenceOf('end_date');

        if ($start_date_present && $end_date_present) {
            if ($this->getStartDate()->getTimestamp() > $this->getEndDate()->getTimestamp()) {
                $errors->addError('Event end date need to be greater than start date', 'date_range');
            } elseif ($this->getStartDate()->daysBetween($this->getEndDate()) >= 365) {
                $errors->addError('Date range can not be longer than one year', 'date_range');
            }
        } else {
            if (empty($start_date_present)) {
                $errors->addError('Event start date is required', 'start_date');
            }

            if (empty($end_date_present)) {
                $errors->addError('Event start date is required', 'end_date');
            }
        }
    }
}
