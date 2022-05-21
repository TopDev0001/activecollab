<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class AvailabilityType extends BaseAvailabilityType
{
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'level' => $this->getLevel(),
                'is_in_use' => $this->isInUse(),
            ]
        );
    }

    public function isAvailable(): bool
    {
        return $this->getLevel() === AvailabilityTypeInterface::LEVEL_AVAILABLE;
    }

    public function isInUse(): bool
    {
        return (bool) DB::executeFirstCell(
            'SELECT COUNT(`id`) as "row_count" FROM `availability_records` WHERE `availability_type_id` = ?',
            $this->getId()
        );
    }

    public function whoCanSeeThis(): array
    {
        return DB::executeFirstColumn(
            'SELECT id FROM users WHERE type IN (?) AND is_archived = ? AND is_trashed = ?',
            [Owner::class, Member::class],
            false,
            false
        );
    }

    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            if (!$this->validateUniquenessOf('name')) {
                $errors->addError(lang('Availability type is already specified'), 'name');
            }
        } else {
            $errors->addError('Name is required', 'name');
        }

        if ($this->validatePresenceOf('level')) {
            if (!in_array($this->getLevel(), AvailabilityTypeInterface::LEVELS)) {
                $errors->addError(lang('Availability level does not exist'), 'level');
            }
        }
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner();
    }

    public function canView(User $user): bool
    {
        return $user->isOwner();
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function getVerboseLevel(Language $language): string
    {
        return $this->isAvailable()
            ? lang('Available', null, true, $language)
            : lang('Unavailable', null, true, $language);
    }
}
