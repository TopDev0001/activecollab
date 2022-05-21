<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('users', SystemModule::NAME);

class UserTimeRecordsController extends UsersController
{
    public function index(Request $request, User $user)
    {
        return TimeRecords::canAccessUsersTimeRecords($user, $this->active_user)
            ? TimeRecords::prepareCollection(
                sprintf(
                    'time_records_by_user_%d_page_%d',
                    $this->active_user->getId(),
                    $request->getPage()
                ),
                $user
            )
            : Response::NOT_FOUND;
    }

    public function filtered_by_date(Request $request, User $user)
    {
        if (!TimeRecords::canAccessUsersTimeRecords($user, $this->active_user)) {
            return Response::NOT_FOUND;
        }

        $from_string = $request->get('from');
        $to_string = $request->get('to');

        $from = $from_string ? DateValue::makeFromString($from_string) : null;
        $to = $to_string ? DateValue::makeFromString($to_string) : null;

        if (empty($from) || empty($to)) {
            return Response::BAD_REQUEST;
        }

        return TimeRecords::prepareCollection(
            sprintf(
                'user_timesheet_report_for_%d_%s:%s',
                $this->active_user->getId(),
                $from->toMySQL(),
                $to->toMySQL()
            ),
            $user
        );
    }
}
