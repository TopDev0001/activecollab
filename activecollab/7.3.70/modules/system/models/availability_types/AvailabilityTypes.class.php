<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\AvailabilityTypeEvents\AvailabilityTypeCreatedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\AvailabilityTypeEvents\AvailabilityTypeDeletedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\AvailabilityTypeEvents\AvailabilityTypeUpdatedEvent;

class AvailabilityTypes extends BaseAvailabilityTypes
{
    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): AvailabilityType
    {
        $availability_type = parent::create($attributes, $save, $announce);

        DataObjectPool::announce(new AvailabilityTypeCreatedEvent($availability_type));

        return $availability_type;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): AvailabilityType
    {
        $availability_type = parent::update($instance, $attributes, $save);

        DataObjectPool::announce(new AvailabilityTypeUpdatedEvent($availability_type));

        return $availability_type;
    }

    public static function scrap(
        DataObject &$instance,
        bool $force_delete = false
    )
    {
        if (parent::count() === 1) {
            throw new LogicException(lang('At least one type is required.'));
        }

        $result = parent::scrap($instance, $force_delete);

        DataObjectPool::announce(new AvailabilityTypeDeletedEvent($instance));

        return $result;
    }
}
