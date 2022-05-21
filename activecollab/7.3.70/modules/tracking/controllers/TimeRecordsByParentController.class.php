<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\TrackingModule;
use Angie\Http\Request;

AngieApplication::useController('tracking_by_parent', TrackingModule::NAME);

class TimeRecordsByParentController extends TrackingByParentController
{
    public function index(Request $request, $user)
    {
        [$from, $to] = $this->prepareQueryParams($request);

        return $this->parent->getTimeRecordsCollection($user, $request->getPage(), $from, $to);
    }

    public function info(Request $request, $user)
    {
        [$from, $to] = $this->prepareQueryParams($request);

        return $this->parent->getTimeRecordsInfo($user, $from, $to);
    }
}
