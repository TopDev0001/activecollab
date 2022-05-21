<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * User workspaces controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class UserWorkspacesController extends AuthRequiredController
{
    /**
     * Show user workspaces.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return UserWorkspaces::findByUser($user);
    }
}
