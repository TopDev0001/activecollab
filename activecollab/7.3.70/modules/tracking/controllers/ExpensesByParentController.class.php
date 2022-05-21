<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\TrackingModule;
use Angie\Http\Request;

AngieApplication::useController('tracking_by_parent', TrackingModule::NAME);

class ExpensesByParentController extends TrackingByParentController
{
    public function info(Request $request, $user)
    {
        [$from, $to] = $this->prepareQueryParams($request);

        return $this->parent->getExpensesInfo($user, $from, $to);
    }
}
