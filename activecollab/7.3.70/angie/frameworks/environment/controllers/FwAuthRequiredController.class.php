<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Controller\Controller;
use Angie\Http\Request;
use Angie\Http\Response\StatusResponse\StatusResponse;

abstract class FwAuthRequiredController extends Controller
{
    /**
     * @param  User|null $user
     * @return mixed
     */
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user instanceof User || !$user->isActive()) {
            return new StatusResponse(
                401,
                'User not authenticated.',
                Users::prepareCollection('initial_for_logged_user', null)
            );
        }

        return null;
    }
}
