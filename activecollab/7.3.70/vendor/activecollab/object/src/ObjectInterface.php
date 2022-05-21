<?php

/*
 * This file is part of the Active Collab Object project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Object;

/**
 * @package ActiveCollab\Object
 */
interface ObjectInterface
{
    /**
     * Return object identifier.
     *
     * @return int
     */
    public function getId();

    /**
     * Check if $this object is same as $object in application scope (persisted, not in memory).
     *
     * @param  object $object
     * @return bool
     */
    public function is($object);
}
