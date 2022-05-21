<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * New Features controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class NewFeaturesController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof Client) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * List new Features.
     *
     * @return array
     */
    public function list_new_features(Request $request, User $user)
    {
        return AngieApplication::newFeatures()->getJson(
            $user,
            new DateValue(),
            true
        );
    }
}
