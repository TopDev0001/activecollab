<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Conversation\SmartConversation;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('conversations', EnvironmentFramework::INJECT_INTO);

class UserConversationsController extends ConversationsController
{
    protected ?ConversationUser $user_conversation = null;

    public function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->user_conversation = $this->conversation->getConversationUser($user);

        if (!$this->user_conversation) {
            if ($this->conversation instanceof SmartConversation) {
                $this->user_conversation = $this->conversation->newMessagesSince($user);
            } else {
                return Response::NOT_FOUND;
            }
        }

        return null;
    }

    public function edit(Request $request, User $user)
    {
        try {
            return ConversationUsers::update($this->user_conversation, $request->put());
        } catch (ValidationErrors $e) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => $e->getErrorsAsString()]
            );
        }
    }
}
