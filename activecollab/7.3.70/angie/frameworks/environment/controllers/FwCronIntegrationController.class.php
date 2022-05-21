<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('integration_singletons', EmailFramework::INJECT_INTO);

/**
 * Cron integrations controller.
 *
 * @package angie.frameworks.email
 * @subpackage controllers
 */
class FwCronIntegrationController extends IntegrationSingletonsController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!($this->active_integration instanceof CronIntegration)) {
            return Response::CONFLICT;
        }
    }

    /**
     * @return array
     */
    public function get()
    {
        return ['single' => $this->active_integration->jsonSerialize()]; // Make sure that we always return fresh values, instead of cached ones
    }
}
