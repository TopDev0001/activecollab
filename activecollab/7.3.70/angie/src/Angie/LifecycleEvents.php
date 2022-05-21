<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

/**
 * External lifecycle event monitor, triggered when object is done with the internal changes.
 *
 * @package Angie
 */
final class LifecycleEvents
{
    const CREATED = 'created';
    const ARCHIVED = 'archived';
    const TRASHED = 'trashed';
    const DELETED = 'deleted';

    /**
     * Return current system state.
     *
     * @return array
     */
    public static function getCurrentState()
    {
    }

    public static function on($type, $action, callable $do, $require_instance = false)
    {
    }
}
