<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\DataObjectPool;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use DataObject;
use DataObjectPool as WrappedDataObjectPool;

class DataObjectPool implements DataObjectPoolInterface
{
    public function get(string $type, ?int $id): ?DataObject
    {
        return WrappedDataObjectPool::get($type, $id, null, false);
    }

    /**
     * Announce that $object changed its state to $new_lifecycle_state.
     *
     * @param  DataObject|DataObjectLifeCycleEventInterface $object
     * @param  string                                       $new_lifecycle_state
     * @return DataObject
     */
    public function announce(
        $object,
        $new_lifecycle_state = WrappedDataObjectPool::OBJECT_CREATED,
        array $attributes = null
    )
    {
        return WrappedDataObjectPool::announce($object, $new_lifecycle_state, $attributes);
    }
}
