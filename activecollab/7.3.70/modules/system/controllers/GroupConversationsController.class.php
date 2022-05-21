<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('conversations', EnvironmentFramework::INJECT_INTO);

class GroupConversationsController extends ConversationsController
{
    public function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!($this->conversation instanceof GroupConversationInterface)) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => lang('This action can be executed on a group conversation only.')]
            );
        }

        return null;
    }

    public function rename(Request $request, User $user)
    {
        try {
            return $this->conversation->rename(
                $user,
                (string) $request->put('name')
            );
        } catch (RuntimeException $e) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => $e->getMessage()]
            );
        }
    }

    public function leave(Request $request, User $user)
    {
        $this->conversation->leave($user);

        return Response::OK;
    }

    public function remove(Request $request, User $by)
    {
        /** @var User $user */
        $user = Users::findById($request->getId('user_id'));

        if (!$user) {
            return Response::BAD_REQUEST;
        }

        try {
            return $this->conversation->remove($user, $by);
        } catch (RuntimeException $e) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => $e->getMessage()]
            );
        }
    }

    public function add_admin(Request $request, User $user)
    {
        /** @var User $new_admin */
        $new_admin = Users::findById($request->getId('user_id'));

        if (!$new_admin) {
            return Response::BAD_REQUEST;
        }

        try {
            return $this->conversation->setAdmin($new_admin, $user);
        } catch (RuntimeException $e) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => $e->getMessage()]
            );
        }
    }

    public function remove_admin(Request $request, User $user)
    {
        /** @var User $new_admin */
        $new_admin = Users::findById($request->getId('user_id'));

        if (!$new_admin) {
            return Response::BAD_REQUEST;
        }

        try {
            return $this->conversation->removeAdmin($new_admin, $user);
        } catch (RuntimeException $e) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => $e->getMessage()]
            );
        }
    }

    public function invite(Request $request, User $user)
    {
        $user_ids = (array) $request->post('user_ids', []);

        if (empty($user_ids)) {
            return Response::BAD_REQUEST;
        }

        try {
            return $this->conversation->invite($user, $user_ids);
        } catch (RuntimeException $e) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => $e->getMessage()]
            );
        }
    }
}
