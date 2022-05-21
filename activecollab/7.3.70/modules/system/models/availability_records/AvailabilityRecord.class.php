<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;

class AvailabilityRecord extends BaseAvailabilityRecord
{
    public function validate(ValidationErrors &$errors)
    {
        if (!$this->validatePresenceOf('user_id')) {
            $errors->addError(lang('User is required'), 'user_id');
        }

        if (!$this->validatePresenceOf('availability_type_id')) {
            $errors->addError(lang('Availability type is required'), 'availability_type_id');
        }

        if (empty(AvailabilityTypes::findById($this->getAvailabilityTypeId()))) {
            $errors->addError(lang('Availability type does not exist.'), 'availability_type_id');
        }

        if (!$this->validatePresenceOf('start_date')) {
            $errors->addError(lang('Start date is required'), 'start_date');
        }

        if (!$this->validatePresenceOf('end_date')) {
            $errors->addError(lang('End date is required'), 'end_date');
        }

        if ($this->getStartDate() > $this->getEndDate()) {
            $errors->addError(lang('Start date need to be before end date'), 'start_date');
        }

        // check does already exist availability for user by given dates
        if (
            $this->getUserId() &&
            $this->getStartDate() &&
            $this->getEndDate() &&
            $this->recordExistsForUserInRange($this->getUserId(), $this->getStartDate(), $this->getEndDate())
        ) {
            $errors->addError(
                lang('Selected dates are overlapping with existing ones.'),
                'start_date',
            );
        }

        $min = DateValue::now()->addDays(-5 * 365);
        if ($this->getStartDate() < $min) {
            $errors->addError(lang('Start date can be max 5 years in the past'), 'start_date');
        }

        $max = DateValue::now()->addDays(5 * 365);
        if ($this->getEndDate() > $max) {
            $errors->addError(lang('End date can be max 5 years in the future'), 'end_date');
        }
    }

    private function recordExistsForUserInRange(int $user_id, DateValue $start_date, DateValue $end_date): bool
    {
        return (bool) DB::executeFirstCell(
            'SELECT COUNT(id) AS "row_count"
                    FROM availability_records
                    WHERE user_id = ?
                        AND (start_date BETWEEN ? AND ? OR end_date BETWEEN ? AND ? OR (start_date <= ? AND end_date >= ?))',
            $user_id,
            $start_date,
            $end_date,
            $start_date,
            $end_date,
            $start_date,
            $end_date,
        );
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'availability_type_id' => $this->getAvailabilityTypeId(),
                'user_id' => $this->getUserId(),
                'message' => $this->getMessage(),
                'start_date' => $this->getStartDate(),
                'end_date' => $this->getEndDate(),
                'duration' => $this->getDuration(),
            ],
        );
    }

    public function whoCanSeeThis(): array
    {
        $user = $this->getUser();
        $members = Users::findByType(Member::class);
        $member_ids = [];
        $result = [];

        if ($members) {
            foreach ($members as $member) {
                if ($member->isPowerUser()) {
                    $result[] = $member->getId();
                }

                $member_ids[] = $member->getId();
            }
        }

        return array_unique(
            array_merge(
                array_intersect($user->getVisibleUserIds(), $member_ids),
                Users::findOwnerIds(),
                $result,
            ),
        );
    }

    public function isPassed(DateValue $date = null): bool
    {
        $date = $date ?? new DateValue();

        return $this->getEndDate()->getTimestamp() < $date->getTimestamp();
    }

    public function canDelete(User $user): bool
    {
        if ($this->isPassed()) {
            return $user->isOwner();
        }

        return $this->getUserId() === $user->getId() || $user->isPowerUser();
    }

    public function isCreatedByAnotherUser(): bool
    {
        return $this->getUserId() !== $this->getCreatedById();
    }

    public function getAvailabilityType(): AvailabilityType
    {
        return AvailabilityTypes::findById($this->getAvailabilityTypeId());
    }

    public function getUser(): User
    {
        return Users::findById($this->getUserId());
    }

    public function getDuration(): int
    {
        if ($this->getStartDate()->isSameDay($this->getEndDate())) {
            return $this->getStartDate()->isDayOff() || $this->getStartDate()->isWeekend() ? 0 : 1;
        }

        return count(
            Globalization::getWorkingDaysBetweenDates(
                clone $this->getStartDate(),
                clone $this->getEndDate(),
            ),
        );
    }
}
