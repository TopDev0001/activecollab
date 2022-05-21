<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

abstract class FwNewCalendarEventNotification extends Notification
{
    use INewInstanceUpdate;

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            /** @var CalendarEvent $event */
            if ($event = $this->getParent()) {
                return [
                    'starts_on' => $event->getStartsOn(),
                    'starts_on_time' => $event->getStartsOnTime(),
                    'calendar' => $event->getCalendar(),
                ];
            }
        }

        return parent::getAdditionalTemplateVars($channel);
    }
}
