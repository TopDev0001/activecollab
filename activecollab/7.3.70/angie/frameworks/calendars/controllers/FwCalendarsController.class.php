<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

// Build on top of backend controller
AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level calendars controller.
 *
 * @package angie.frameworks.calendars
 * @subpackage controllers
 */
abstract class FwCalendarsController extends AuthRequiredController
{
    /**
     * Selected calendar.
     *
     * @var Calendar
     */
    protected $active_calendar;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_calendar = DataObjectPool::get('Calendar', $request->getId('calendar_id'));
    }

    /**
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Calendars::prepareCollection('calendars_by_user_' . $user->getId(), $user);
    }

    /**
     * @return Calendar|int
     */
    public function add(Request $request, User $user)
    {
        return Calendars::canAdd($user) ? Calendars::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * @return Calendar|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_calendar->isLoaded() && $this->active_calendar->canView($user) ? $this->active_calendar : Response::NOT_FOUND;
    }

    /**
     * @return Calendar|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_calendar->isLoaded() && $this->active_calendar->canEdit($user) ? Calendars::update($this->active_calendar, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Drop selected calendar.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_calendar->isLoaded() && $this->active_calendar->canDelete($user) ? Calendars::scrap($this->active_calendar) : Response::NOT_FOUND;
    }
}
