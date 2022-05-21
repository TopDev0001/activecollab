<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Calendar feed interface.
 *
 * @package angie.frameworks.calendars
 * @subpackage models
 */
interface ICalendarFeed
{
    /**
     * Export calendar to iCalendar file.
     *
     * @return string
     */
    public function exportCalendarToFile(User $user);

    /**
     * @return string
     */
    public function getCalendarElementSummaryPrefix();

    /**
     * @return string
     */
    public function getCalendarElementSummarySufix();
}
