<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

trait ICalendarFeedElementImplementation
{
    public function skipCalendarFeed(): bool
    {
        return false;
    }

    public function getCalendarFeedUID(): string
    {
        $id = $this->getId();
        $type = $this->getVerboseType();
        $prefix = 'ac';

        if ($this instanceof CalendarEvent) {
            $prefix .= "_calendar_{$this->getCalendar()->getId()}";
        } elseif ($this instanceof IProjectElement) {
            $prefix .= "_project_{$this->getProject()->getId()}";
        }

        $timestamp = $this->getCreatedOn()->getTimestamp();

        return md5("{$prefix}_{$type}_{$id}_{$timestamp}");
    }

    public function getCalendarFeedSummary(
        IUser $user,
        string $prefix = '',
        string $sufix = ''
    ): string
    {
        return $prefix . $this->getName() . $sufix;
    }

    public function getCalendarFeedDescription(IUser $user): ?string
    {
        return null;
    }

    public function getCalendarFeedDateStart()
    {
        return null;
    }

    public function getCalendarFeedDateEnd()
    {
        return null;
    }

    public function getCalendarFeedRepeatingRule()
    {
        return null;
    }
}
