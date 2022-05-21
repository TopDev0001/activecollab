<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Reports;

AngieApplication::useController('reports', EnvironmentFramework::INJECT_INTO);

/**
 * Data filters controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwDataFiltersController extends ReportsController
{
    /**
     * Selected data filter.
     *
     * @var DataFilter
     */
    protected $active_data_filter;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_data_filter = DataObjectPool::get('DataFilter', $request->getId('data_filter_id'));

        if ($this->active_data_filter instanceof DataFilter && !$this->active_data_filter->canView($user)) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * Show tracking report form and options.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        return DataFilters::prepareCollection('filters_for_' . $user->getId(), $user);
    }

    /**
     * View selected filter.
     */
    public function view()
    {
        return $this->active_data_filter instanceof DataFilter ? $this->active_data_filter : Response::NOT_FOUND;
    }

    public function run(Request $request, User $user)
    {
        return Response::NOT_FOUND; // Don't access directly, use /reports/run
    }

    public function export(Request $request, User $user)
    {
        return Response::NOT_FOUND; // Don't access directly, use /reports/export
    }

    /**
     * Create new filter.
     *
     * @return DataFilter|int
     */
    public function add(Request $request, User $user)
    {
        $type = $request->post('type');

        if ($type && DataFilters::canAdd($type, $user)) {
            return DataFilters::create($request->post());
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Update an existing filter.
     *
     * @return DataFilter|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_data_filter instanceof DataFilter && $this->active_data_filter->canEdit($user) ? DataFilters::update($this->active_data_filter, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Drop an existing filter.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_data_filter instanceof DataFilter && $this->active_data_filter->canDelete($user) ? DataFilters::scrap($this->active_data_filter) : Response::NOT_FOUND;
    }
}
