<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('teams', SystemModule::NAME);

class TeamMembersController extends TeamsController
{
    /**
     * Selected project user.
     *
     * @var User
     */
    protected $active_user;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($this->active_team->isNew()) {
            return Response::NOT_FOUND;
        }

        $user_id = $request->getId('user_id');

        if ($user_id) {
            $user = DataObjectPool::get('User', $user_id);

            if ($user instanceof User) {
                if ($this->active_team->isMember($user)) {
                    $this->active_user = $user;
                } else {
                    return Response::NOT_FOUND;
                }
            }
        }
    }

    public function index(Request $request, User $user)
    {
        return $this->active_team->getMemberIds();
    }

    /**
     * Add people to the project.
     *
     * @return array|DataObject|int
     */
    public function add(Request $request, User $user)
    {
        if (!$this->active_team->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        $user_ids = $request->post();
        $users = $user_ids && is_foreachable($user_ids) ? Users::findByIds($user_ids) : null;

        if ($users && is_foreachable($users)) {
            $this->active_team->addMembers($users);

            return $this->active_team->getMemberIds();
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Replace user.
     *
     * @return int
     */
    public function delete(Request $request, User $user)
    {
        if ($this->active_team->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        if ($this->active_user->isLoaded()) {
            $this->active_team->removeMembers([$this->active_user], ['by' => $user]);

            return Response::OK;
        }

        return Response::NOT_FOUND;
    }
}
