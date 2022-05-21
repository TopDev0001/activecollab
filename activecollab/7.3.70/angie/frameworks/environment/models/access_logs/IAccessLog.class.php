<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Access log interface.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
interface IAccessLog
{
    /**
     * Return true if $user can view access logs.
     *
     * @return bool
     */
    public function canViewAccessLogs(User $user);

    /**
     * Return number of downloads.
     *
     * @return int
     */
    public function getDownloadsCount();

    // ---------------------------------------------------
    //  Requirements
    // ---------------------------------------------------

    /**
     * @return int
     */
    public function getId();
}
