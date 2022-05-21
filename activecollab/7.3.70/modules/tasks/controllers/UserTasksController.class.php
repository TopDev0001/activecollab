<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('users', SystemModule::NAME);

class UserTasksController extends UsersController
{
    /**
     * Show user assignments.
     *
     * @return ModelCollection|int
     */
    public function index(Request $request, User $user)
    {
        if ($user->isLoaded()) {
            if (($user->isClient() && !$user->isPowerClient()) || !$this->active_user->canView($user)) {
                return Response::NOT_FOUND;
            }

            if ($user->isClient() && $user->getId() != $this->active_user->getId()) {
                return Response::NOT_FOUND;
            }

            return Users::prepareCollection('open_assignments_for_assignee_' . $this->active_user->getId(), $user);
        }

        return Response::NOT_FOUND;
    }
}
