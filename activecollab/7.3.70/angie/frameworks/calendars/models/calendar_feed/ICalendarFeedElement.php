<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface ICalendarFeedElement
{
    public function skipCalendarFeed(): bool;
    public function getCalendarFeedUID(): string;
    public function getCalendarFeedSummary(
        IUser $user,
        string $prefix = '',
        string $sufix = ''
    ): string;
    public function getCalendarFeedDescription(IUser $user): ?string;

    /**
     * Return event start date.
     *
     * @return DateValue|DateTimeValue|null
     */
    public function getCalendarFeedDateStart();

    /**
     * Return event end date.
     *
     * @return DateValue|DateTimeValue|null
     */
    public function getCalendarFeedDateEnd();

    /**
     * Return event repeating rule.
     *
     * @return string|null
     */
    public function getCalendarFeedRepeatingRule();
}
