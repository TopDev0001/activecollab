<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('project', SystemModule::NAME);

class ProjectMembersController extends ProjectController
{
    /**
     * Selected project user.
     *
     * @var User
     */
    protected $active_user;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        if ($user_id = $request->getId('user_id')) {
            $user = DataObjectPool::get('User', $user_id);

            if ($user instanceof User) {
                if ($this->active_project->isMember($user)) {
                    $this->active_user = $user;
                } else {
                    return Response::NOT_FOUND;
                }
            }
        }
    }

    /**
     * Show people page.
     *
     * @return array
     */
    public function index(Request $request, User $user)
    {
        return $this->active_project->getMemberIds();
    }

    public function responsibilities(): array
    {
        return $this->active_project->countResponsibilities();
    }

    /**
     * Add people to the project.
     *
     * @return array|DataObject|int
     */
    public function add(Request $request, User $user)
    {
        if ($this->active_project->canManagePeople($user)) {
            $user_ids = $request->post();
            $users = $user_ids && is_foreachable($user_ids) ? Users::findByIds($user_ids) : null;

            if ($users && is_foreachable($users)) {
                $this->active_project->addMembers($users);

                return $this->active_project->getMemberIds();
            }

            return Response::BAD_REQUEST;
        }

        return Response::FORBIDDEN;
    }

    /**
     * Remove user from a project.
     *
     * @return int
     */
    public function delete(Request $request, User $user)
    {
        if ($this->active_project->canManagePeople($user)) {
            if ($this->active_user->isLoaded()) {
                $this->active_project->removeMembers([$this->active_user], ['by' => $user]);

                return $this->active_project->getMemberIds();
            }

            return Response::NOT_FOUND;
        }

        return Response::FORBIDDEN;
    }

    /**
     * Remove user from this project.
     *
     * @return int|array
     */
    public function replace(Request $request, User $user)
    {
        if (!$this->active_project->canManagePeople($user)) {
            return Response::FORBIDDEN;
        }

        if (!$this->active_user->isLoaded()) {
            return Response::NOT_FOUND;
        }

        $replace_with_user_id = $request->put('replace_with_id');
        $replace_with_user = $replace_with_user_id ? Users::findById($replace_with_user_id) : null;

        if ($replace_with_user instanceof User) {
            $this->active_project->replaceMember(
                $this->active_user,
                $replace_with_user,
                [
                    'by' => $user,
                    'send_notification' => $request->put('send_notification'),
                ]
            );

            if ($request->put('send_notification')) {
                /** @var ReplacingProjectUserNotification $notification */
                $notification = AngieApplication::notifications()
                    ->notifyAbout('system/replacing_project_user', $this->active_project);

                $notification
                    ->setReplacingUser($this->active_user)
                    ->sendToUsers($replace_with_user);
            }

            return $this->active_project->getMemberIds();
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Revoke client access.
     *
     * @return int
     */
    public function revoke_client_access(Request $request, User $user)
    {
        if ($this->active_project->canManagePeople($user)) {
            return $this->active_project->revokeClientAccess($user);
        }

        return Response::NOT_FOUND;
    }
}
