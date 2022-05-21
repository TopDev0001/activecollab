<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Notifications\PushNotificationInterface;

/**
 * Framework level new calendar event notification.
 *
 * @package ActiveCollab.modules.system
 * @subpackage notifications
 */
class NewCalendarEventNotification extends FwNewCalendarEventNotification implements PushNotificationInterface
{
    use INewInstanceUpdate; use INewProjectElementNotificationOptOutConfig;

    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
        $calendar_event = $this->getParent();

        if ($calendar_event instanceof CalendarEvent && (empty($type_ids_map[Calendar::class]) || !in_array($calendar_event->getCalendarId(), $type_ids_map[Calendar::class]))) {
            $type_ids_map[Calendar::class][] = $calendar_event->getCalendarId();
        }
    }
}
