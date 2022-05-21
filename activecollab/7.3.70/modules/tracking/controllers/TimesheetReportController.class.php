<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

class TimesheetReportController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->isPowerUser()) {
            return Response::FORBIDDEN;
        }

        return null;
    }

    public function index(Request $request, User $user)
    {
        $from_string = $request->get('from');
        $to_string = $request->get('to');

        $from = $from_string ? DateValue::makeFromString($from_string) : null;
        $to = $to_string ? DateValue::makeFromString($to_string) : null;

        if (!($from instanceof DateValue) || !($to instanceof DateValue)) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                [
                    'message' => lang('Invalid timesheet period.'),
                    'type' => 'error',
                ]
            );
        }

        return TimeRecords::prepareCollection(
            'timesheet_report_' . $from->toMySQL() . ':' . $to->toMySQL(),
            $user
        );
    }
}
