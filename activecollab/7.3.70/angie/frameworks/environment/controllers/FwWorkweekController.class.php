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
 * Workweek controller.
 *
 * @package angie.framework.environment
 * @subpackage controllers
 */
class FwWorkweekController extends AuthRequiredController
{
    /**
     * Return workweek settings.
     *
     * @return mixed
     */
    public function show_settings()
    {
        return ConfigOptions::getValue(['time_first_week_day', 'time_workdays']);
    }

    /**
     * Save workweek settings.
     *
     * @return array|int
     */
    public function save_settings(Request $request, User $user)
    {
        if (!$user->isOwner()) {
            return Response::NOT_FOUND;
        }

        $put = $request->put();

        return ConfigOptions::setValue([
            'time_first_week_day' => (int) $put['time_first_week_day'],
            'time_workdays' => (array) $put['time_workdays'],
        ]);
    }
}
