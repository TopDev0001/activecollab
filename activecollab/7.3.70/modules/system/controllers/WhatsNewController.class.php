<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;

AngieApplication::useController('auth_required', SystemModule::NAME);

class WhatsNewController extends AuthRequiredController
{
    public function index(Request $request, User $user)
    {
        if ($request->get('from') && $request->get('to')) {
            $from = DateValue::makeFromString((string) $request->get('from'));
            $to = DateValue::makeFromString((string) $request->get('to'));

            return Users::prepareCollection(
                sprintf(
                    'range_activity_logs_for_%s_%s:%s_page_%s',
                    $user->getId(),
                    $from->toMySQL(),
                    $to->toMySQL(),
                    $request->getPage()
                ),
                $user
            );
        }

        return Users::prepareCollection(
            'activity_logs_for_' . $user->getId() . '_page_' . $request->getPage(),
            $user
        );
    }

    public function daily(Request $request, User $user)
    {
        return Users::prepareCollection(
            'daily_activity_logs_for_' . $user->getId() . '_' . $request->get('day') . '_page_' . $request->getPage(),
            $user
        );
    }
}
