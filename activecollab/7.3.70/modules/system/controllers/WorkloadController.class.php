<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', SystemModule::NAME);

class WorkloadController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        if (!$user->isPowerUser()) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => lang('Access not allowed.')]
            );
        }

        return null;
    }

    public function workload_projects(Request $request, $user)
    {
        try {
            return Projects::prepareCollection('workload_projects', $user);
        } catch (Exception $e) {
            AngieApplication::log()->error('Failed to create workload projects collection.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return new StatusResponse(
                Response::NOT_FOUND,
                '',
                ['message' => lang('Failed to fetch your Worklod projects. Please contact our support.')]
            );
        }
    }

    public function workload_tasks(Request $request, $user)
    {
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        if (empty($start_date) || empty($end_date)) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => lang('Start and end date are required.')]
            );
        }

        $filter = $request->get('filter', 'no-filter');

        try {
            return Tasks::prepareCollection(
                sprintf(
                    'workload_filter_%s_start_%s_end_%s',
                    $filter,
                    $start_date,
                    $end_date
                ),
                $user
            );
        } catch (Exception $e) {
            AngieApplication::log()->error('Failed to create workload collection.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return new StatusResponse(
                Response::NOT_FOUND,
                '',
                ['message' => lang('Failed to create your Worklod information. Please contact our support.')]
            );
        }
    }
}
