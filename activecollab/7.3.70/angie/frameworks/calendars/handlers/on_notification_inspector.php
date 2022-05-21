<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_notification_inspector event handler implementation.
 *
 * @package angie.frameworks.calendars
 * @subpackage handlers
 */

/**
 * Handle on_notification_inspector event.
 *
 * @param IProjectElement $context
 * @param mixed           $subcontext
 * @param IUser           $recipient
 * @param Language        $language
 * @param Angie\NamedList $properties
 * @param string          $link_style
 */
function calendars_handle_on_notification_inspector(&$context, &$subcontext, &$recipient, &$language, &$properties, $link_style)
{
    if ($context instanceof CalendarEvent) {
        $calendar = $context->getCalendar();

        if ($calendar instanceof Calendar) {
            $properties->beginWith('calendar', [
                'label' => lang('Calendar', null, null, $language),
                'value' => clean($calendar->getName()),
            ]);
        }
    }
}
