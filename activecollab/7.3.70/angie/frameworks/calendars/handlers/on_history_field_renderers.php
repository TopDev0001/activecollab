<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Handle on_history_field_renderers event.
 *
 * @package angie.framework.calendars
 * @subpackage handlers
 */

/**
 * Get history changes as log text.
 *
 * @param ApplicationObject $object
 * @param array             $renderers
 */
function calendars_handle_on_history_field_renderers($object, &$renderers)
{
    if ($object instanceof Calendar) {
        $renderers['color'] = function ($old_value, $new_value) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Color changed from <span style="color: #:old_value;">&#9608;</span> to <span style="color: #:new_value">&#9608;</span>', ['old_value' => $old_value, 'new_value' => $new_value]);
                } else {
                    return lang('Color set to <span style="color: #:new_value">&#9608;</span>', ['new_value' => $new_value]);
                }
            } else {
                if ($old_value) {
                    return lang('Color removed');
                }
            }
        };
    } elseif ($object instanceof CalendarEvent) {
        $renderers['parent_id'] = function ($old_value, $new_value) {
            $new_calendar = Calendars::findById($new_value);
            $old_calendar = Calendars::findById($old_value);

            if ($new_calendar instanceof Calendar) {
                if ($old_calendar instanceof Calendar) {
                    return lang('Calendar changed from <b>:old_value</b> to <b>:new_value</b>', [
                        'old_value' => $old_calendar->getName(),
                        'new_value' => $new_calendar->getName(),
                    ]);
                } else {
                    return lang('Calendar set to <b>:new_value</b>', [
                        'new_value' => $new_calendar->getName(),
                    ]);
                }
            } else {
                if ($old_calendar instanceof Calendar || is_null($new_calendar)) {
                    return lang('Calendar set to empty value');
                }
            }
        };

        $renderers['repeat_event'] = function ($old_value, $new_value) {
            $repeat_types_map = [
                CalendarEvent::DONT_REPEAT => lang('do not repeat'),
                CalendarEvent::REPEAT_DAILY => lang('daily'),
                CalendarEvent::REPEAT_MONTHLY => lang('monthly'),
                CalendarEvent::REPEAT_WEEKLY => lang('weekly'),
                CalendarEvent::REPEAT_YEARLY => lang('yearly'),
            ];

            if ($new_value) {
                if ($old_value) {
                    return lang('Repeat period changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => array_var($repeat_types_map, $old_value, lang('unknown')), 'new_value' => array_var($repeat_types_map, $new_value, lang('unknown'))]);
                } else {
                    return lang('Repeat period set to <b>:new_value</b>', ['new_value' => array_var($repeat_types_map, $new_value, lang('unknown'))]);
                }
            } else {
                if ($old_value) {
                    return lang('Repeat period removed');
                }
            }
        };

        $renderers['starts_on'] = function ($old_value, $new_value) {
            $new_date = DateValue::makeFromString($new_value);
            $old_date = DateValue::makeFromString($old_value);

            $logged_user = AngieApplication::authentication()->getLoggedUser();

            if ($new_date instanceof DateValue) {
                if ($old_date instanceof DateValue) {
                    return lang('Starts on changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_date->formatDateForUser($logged_user), 'new_value' => $new_date->formatDateForUser($logged_user)]);
                } else {
                    return lang('Starts on set to <b>:new_value</b>', ['new_value' => $new_date->formatDateForUser($logged_user)]);
                }
            } else {
                if ($old_date instanceof DateValue) {
                    return lang('Starts on set to empty value');
                } else {
                    return lang('Starts on unknown');
                }
            }
        };

        $renderers['ends_on'] = function ($old_value, $new_value) {
            $new_date = DateValue::makeFromString($new_value);
            $old_date = DateValue::makeFromString($old_value);

            $logged_user = AngieApplication::authentication()->getLoggedUser();

            if ($new_date instanceof DateValue) {
                if ($old_date instanceof DateValue) {
                    return lang('Ends on changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_date->formatDateForUser($logged_user), 'new_value' => $new_date->formatDateForUser($logged_user)]);
                } else {
                    return lang('Ends on set to <b>:new_value</b>', ['new_value' => $new_date->formatDateForUser($logged_user)]);
                }
            } else {
                if ($old_date instanceof DateValue) {
                    return lang('Ends on set to empty value');
                } else {
                    return lang('Ends on unknown');
                }
            }
        };

        $renderers['starts_on_time'] = function ($old_value, $new_value) {
            $new_date = DateTimeValue::makeFromString($new_value);
            $old_date = DateTimeValue::makeFromString($old_value);

            $logged_user = AngieApplication::authentication()->getLoggedUser();

            if ($new_date instanceof DateTimeValue) {
                if ($old_date instanceof DateTimeValue) {
                    return lang('Time changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_date->formatTimeForUser($logged_user), 'new_value' => $new_date->formatTimeForUser($logged_user)]);
                } else {
                    return lang('Time set to <b>:new_value</b>', ['new_value' => $new_date->formatTimeForUser($logged_user)]);
                }
            } else {
                if ($old_date instanceof DateTimeValue) {
                    return lang('Time removed');
                }
            }
        };
    }
}
