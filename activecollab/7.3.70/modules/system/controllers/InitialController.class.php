<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Application level initialisation controller.
 *
 * @package activeCollab.modules.system
 * @subpackage controllers
 */
class InitialController extends AuthRequiredController
{
    /**
     * @param  User                  $user
     * @return ModelCollection|array
     */
    public function index(Request $request, $user)
    {
        return Users::prepareCollection('initial', $user);
    }

    /**
     * Test controller action speed.
     */
    public function test_action_speed()
    {
        return [
            'action_speed' => round(microtime(true) - ANGIE_SCRIPT_TIME, 5),
        ];
    }
}
