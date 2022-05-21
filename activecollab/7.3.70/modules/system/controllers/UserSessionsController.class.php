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

class UserSessionsController extends UsersController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($this->active_user->isNew()) {
            return Response::NOT_FOUND;
        }

        if ($this->active_user->getId() != $user->getId() && !$user->isOwner()) {
            return Response::FORBIDDEN;
        }

        return null;
    }

    /**
     * User sessions.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return UserSessions::prepareCollection('user_sessions_for_' . $this->active_user->getId(), $user);
    }

    /**
     * Remove selected session.
     */
    public function remove()
    {
        return Response::NOT_FOUND;
    }
}
