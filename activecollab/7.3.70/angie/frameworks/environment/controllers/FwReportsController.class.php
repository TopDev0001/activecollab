<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\FileDownload\FileDownload;
use Angie\Reports;
use Angie\Reports\Report;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

abstract class FwReportsController extends AuthRequiredController
{
    protected bool $check_reports_access_permissions = true;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->canUseReports()) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * List reports that are available to $user.
     *
     * @return array
     */
    public function index(Request $request, User $user)
    {
        return Reports::getAvailableReportsFor($user);
    }

    /**
     * @return array|int
     */
    public function run(Request $request, User $user)
    {
        [$type, $attributes] = $this->prepareTypeAndAttributes($request->get());

        $report = Reports::getReport($type, $attributes);

        if ($report instanceof Report && $report->canRun($user)) {
            try {
                $result = $report->run($user);
            } catch (DataFilterConditionsError $e) {
                $result = [];
            }

            return empty($result) ? [] : $result;
        }

        return Response::NOT_FOUND;
    }

    /**
     * @return FileDownload|int
     */
    public function export(Request $request, User $user)
    {
        [$type, $attributes] = $this->prepareTypeAndAttributes($request->get());

        $report = Reports::getReport($type, $attributes);

        if ($report instanceof Report && $report->canRun($user)) {
            if ($file = $report->export($user)) {
                return new FileDownload($file, 'text/csv', 'export.csv');
            }

            return Response::OPERATION_FAILED;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Use $_GET and return data that we need to create a report instance.
     *
     * @param  array                $from
     * @return array
     */
    private function prepareTypeAndAttributes($from)
    {
        foreach (['module', 'controller', 'action'] as $field) {
            if (isset($from[$field])) {
                unset($from[$field]);
            }
        }

        return [
            (string) array_required_var($from, 'type', true),
            $from,
        ];
    }
}
