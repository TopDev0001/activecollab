<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;

AngieApplication::useController('selected_object', EnvironmentFramework::INJECT_INTO);

/**
 * Since last visit controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
class FwSinceLastVisitController extends SelectedObjectController
{
    /**
     * Selected object.
     *
     * @var ApplicationObject
     */
    protected $active_object;

    /**
     * Return last visit timestamp.
     *
     * @return array
     */
    public function index(Request $request, User $user)
    {
        $params = $request->getQueryParams();

        $delay = isset($params['delay']) ? (int) $params['delay'] : null;

        return [
            'last_visit_timestamp' => (new SinceLastVisitService($user))
                ->getLastVisitTimestamp(
                    $this->active_object,
                    $delay
                ),
        ];
    }
}
