<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Modules\AngieFramework;

class CalendarsFramework extends AngieFramework
{
    const NAME = 'calendars';

    protected string $name = 'calendars';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            [
                Calendar::class,
                UserCalendar::class,
            ],
            function (array $ids): ?iterable
            {
                return Calendars::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            CalendarEvent::class,
            function (array $ids): ?iterable
            {
                return CalendarEvents::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                FwCalendar::class => __DIR__ . '/models/calendars/FwCalendar.class.php',
                FwCalendars::class => __DIR__ . '/models/calendars/FwCalendars.class.php',

                FwUserCalendar::class => __DIR__ . '/models/FwUserCalendar.class.php',

                FwCalendarEvent::class => __DIR__ . '/models/calendar_events/FwCalendarEvent.class.php',
                FwCalendarEvents::class => __DIR__ . '/models/calendar_events/FwCalendarEvents.class.php',

                FwNewCalendarEventNotification::class => __DIR__ . '/notifications/FwNewCalendarEventNotification.class.php',

                ICalendarFeed::class => __DIR__ . '/models/calendar_feed/ICalendarFeed.php',
                ICalendarFeedImplementation::class => __DIR__ . '/models/calendar_feed/ICalendarFeedImplementation.php',
                ICalendarFeedElement::class => __DIR__ . '/models/calendar_feed/ICalendarFeedElement.php',
                ICalendarFeedElementImplementation::class => __DIR__ . '/models/calendar_feed/ICalendarFeedElementImplementation.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_trash_sections');
        $this->listen('on_history_field_renderers');
        $this->listen('on_rebuild_activity_logs');
        $this->listen('on_notification_inspector');
    }
}
