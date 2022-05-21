<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Calendar events class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class CalendarEvents extends FwCalendarEvents
{
    /**
     * Returns true if $user can create a new events.
     *
     * @return bool
     */
    public static function canAdd(User $user, Calendar $calendar)
    {
        if ($user instanceof Client) {
            return false;
        }

        return parent::canAdd($user, $calendar);
    }

    /**
     * Return new collection.
     *
     * @param  User|null                 $user
     * @return ModelCollection
     * @throws InvalidParamError
     * @throws ImpossibleCollectionError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'all_events_in_calendar')) {
            $collection = parent::prepareCollection($collection_name, $user);
            self::prepareCalendarEventsCollectionByCalendar($collection, $collection_name);
        } elseif (str_starts_with($collection_name, 'all_events_for_period')) {
            $collection = parent::prepareCollection($collection_name, $user);
            self::prepareCalendarEventsCollectionForPeriod($collection, $collection_name, $user);
        } elseif (str_starts_with($collection_name, 'assignments_as_calendar_events')) {
            $bits = explode('_', $collection_name);

            $to = new DateValue(array_pop($bits));
            $from = new DateValue(array_pop($bits));
            $assignee_id = array_pop($bits);

            $assignee = DataObjectPool::get(User::class, $assignee_id);

            $collection = new AssignmentsAsCalendarEventsCollection($collection_name);
            $collection->setWhosAsking($user);
            $collection->setFromDate($from);
            $collection->setToDate($to);

            if ($assignee instanceof User) {
                $collection->setAssignee($assignee);
            }
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
        }

        return $collection;
    }

    /**
     * Prepare calendar events collection by filtered by calendar ID.
     *
     * @param  string                    $collection_name
     * @throws ImpossibleCollectionError
     * @throws InvalidParamError
     */
    private static function prepareCalendarEventsCollectionByCalendar(ModelCollection &$collection, $collection_name)
    {
        $parts = explode('_', $collection_name);
        $calendar_id = array_pop($parts);

        /** @var Calendar $calendar */
        if ($calendar = DataObjectPool::get(Calendar::class, $calendar_id)) {
            $to = array_pop($parts);
            $from = array_pop($parts);

            $collection->setOrderBy('starts_on');
            $collection->setConditions('calendar_id = ? AND is_trashed = ? AND (((starts_on BETWEEN ? AND ?) OR (ends_on BETWEEN ? AND ?) OR (starts_on < ? AND ends_on > ?)) OR (repeat_event IN (?) AND (repeat_until >= ? OR repeat_until IS NULL)))', $calendar->getId(), false, $from, $to, $from, $to, $from, $to, [CalendarEvent::REPEAT_DAILY, CalendarEvent::REPEAT_WEEKLY, CalendarEvent::REPEAT_MONTHLY, CalendarEvent::REPEAT_YEARLY], $from);
        } else {
            throw new ImpossibleCollectionError("Calendar #{$calendar_id} not found");
        }
    }

    /**
     * Prepare calendar events collection by period.
     *
     * @param  string                    $collection_name
     * @param  User|null                 $user
     * @throws ImpossibleCollectionError
     */
    private static function prepareCalendarEventsCollectionForPeriod(ModelCollection &$collection, $collection_name, $user)
    {
        $parts = explode('_', $collection_name);

        $to = array_pop($parts);
        $from = array_pop($parts);

        if ($calendar_ids = Calendars::findIdsByUser($user, DB::prepare('is_trashed = ?', false))) {
            $collection->setConditions('calendar_id IN (?) AND is_trashed = ? AND (((starts_on BETWEEN ? AND ?) OR (ends_on BETWEEN ? AND ?) OR (starts_on < ? AND ends_on > ?)) OR (repeat_event IN (?) AND (repeat_until >= ? OR repeat_until IS NULL)))', $calendar_ids, false, $from, $to, $from, $to, $from, $to, [CalendarEvent::REPEAT_DAILY, CalendarEvent::REPEAT_WEEKLY, CalendarEvent::REPEAT_MONTHLY, CalendarEvent::REPEAT_YEARLY], $from);
            $collection->setOrderBy('starts_on');
        } else {
            throw new ImpossibleCollectionError('User has no owned or shared calendar');
        }
    }
}
