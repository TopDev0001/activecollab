<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IAccessLogImplementation
{
    /**
     * Return true if $user can view access logs.
     *
     * @return bool
     */
    public function canViewAccessLogs(User $user)
    {
        return $user->isOwner();
    }

    /**
     * Return number of file downloads.
     *
     * @return int
     */
    public function getDownloadsCount()
    {
        return (int) DB::executeFirstCell('SELECT COUNT(id) FROM access_logs WHERE ' . AccessLogs::parentToCondition($this) . ' AND is_download = ?', true);
    }

    /**
     * Return ID of this object.
     *
     * @return int
     */
    abstract public function getId();
}
