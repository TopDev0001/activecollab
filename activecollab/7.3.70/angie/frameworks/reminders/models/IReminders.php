<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface IReminders
{
    /**
     * Return reminders.
     *
     * @return Reminder[]|null
     */
    public function getReminders();

    /**
     * @return int
     */
    public function getId();

    public function canView(User $user): bool;
}
