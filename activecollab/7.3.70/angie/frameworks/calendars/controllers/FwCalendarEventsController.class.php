<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('calendars', CalendarsFramework::INJECT_INTO);

/**
 * Framework level calendar events controller.
 *
 * @package angie.frameworks.calendars
 * @subpackage controllers
 */
abstract class FwCalendarEventsController extends CalendarsController
{
    /**
     * Selected and loaded calendar event.
     *
     * @var CalendarEvent
     */
    protected $active_calendar_event;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($this->active_calendar->isNew()) {
            return Response::NOT_FOUND;
        }

        $this->active_calendar_event = DataObjectPool::get('CalendarEvent', $request->getId('calendar_event_id'));

        if (empty($this->active_calendar_event)) {
            $this->active_calendar_event = new CalendarEvent();
        }
    }

    /**
     * Return collection of calendar events.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return CalendarEvents::prepareCollection('all_events_in_calendar_' . $request->get('from') . '_' . $request->get('to') . '_' . $this->active_calendar->getId(), $user);
    }

    /**
     * Create new event.
     *
     * @return CalendarEvent|int
     */
    public function add(Request $request, User $user)
    {
        if (CalendarEvents::canAdd($user, $this->active_calendar)) {
            $put = $request->post();
            $put['calendar_id'] = $this->active_calendar->getId();

            return CalendarEvents::create($put);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Show details of a specific event.
     *
     * @return CalendarEvent|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_calendar_event->isLoaded() && $this->active_calendar->getId() == $this->active_calendar_event->getCalendarId() && ($this->active_calendar_event->canView($user) || $this->active_calendar->canView($user)) ? $this->active_calendar_event : Response::NOT_FOUND;
    }

    /**
     * Update an existing event.
     *
     * @return CalendarEvent|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_calendar_event->isLoaded() && $this->active_calendar->getId() == $this->active_calendar_event->getCalendarId() && ($this->active_calendar_event->canEdit($user) || $this->active_calendar->canEdit($user)) ? CalendarEvents::update($this->active_calendar_event, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Delete existing template.
     *
     * @return int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_calendar_event->isLoaded() && $this->active_calendar->getId() == $this->active_calendar_event->getCalendarId() && ($this->active_calendar_event->canDelete($user) || $this->active_calendar->canDelete($user)) ? CalendarEvents::scrap($this->active_calendar_event) : Response::NOT_FOUND;
    }
}
