<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Etag interface.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
interface IEtag
{
    /**
     * Return true if this object can be tagged and cached on client side.
     *
     * @return bool|null
     */
    public function canBeTagged();

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true);

    /**
     * Check if provided etag value match the current record.
     *
     * @param  string $value
     * @param  bool   $use_cache
     * @return bool
     */
    public function validateTag($value, IUser $user, $use_cache = true);
}
