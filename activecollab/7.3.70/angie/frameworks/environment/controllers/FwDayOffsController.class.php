<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Days off controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
class FwDayOffsController extends AuthRequiredController
{
    /**
     * @var DayOff
     */
    protected $active_day_off;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_day_off = DataObjectPool::get('DayOff', $request->getId('day_off_id'));
        if (empty($this->active_day_off)) {
            $this->active_day_off = new DayOff();
        }
    }

    /**
     * Return all days off.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return DayOffs::prepareCollection('day_offs', $user);
    }

    /**
     * Create a new day off.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return DayOffs::canAdd($user) ? DayOffs::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * Return a single day off.
     *
     * @return DayOff|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_day_off->isLoaded() && $this->active_day_off->canView($user) ? $this->active_day_off : Response::NOT_FOUND;
    }

    /**
     * Update a day off.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_day_off->isLoaded() && $this->active_day_off->canEdit($user) ? DayOffs::update($this->active_day_off, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Delete a day off.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_day_off->isLoaded() && $this->active_day_off->canDelete($user) ? DayOffs::scrap($this->active_day_off) : Response::NOT_FOUND;
    }
}
