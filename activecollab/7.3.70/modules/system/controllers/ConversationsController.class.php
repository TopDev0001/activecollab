<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationFactoryInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationResolverInterface;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class ConversationsController extends AuthRequiredController
{
    protected ?ConversationInterface $conversation = null;

    public function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof Client) {
            return Response::NOT_FOUND;
        }

        if ($conversation_id = $request->getId('conversation_id')) {
            $this->conversation = DataObjectPool::get(Conversation::class, $conversation_id);

            if (!$this->conversation) {
                return Response::NOT_FOUND;
            }

            if (!$this->conversation->isMember($user)) {
                return Response::FORBIDDEN;
            }
        }

        return null;
    }

    public function index(Request $request, User $user)
    {
        return Conversations::prepareCollection('user_conversations', $user);
    }

    public function create(Request $request, User $user)
    {
        $type = (string) $request->post('type');
        $value = $request->post('value');

        try {
            return AngieApplication::getContainer()
                ->get(ConversationFactoryInterface::class)
                ->create($user, $type, $value);
        } catch (Exception $e) {
            AngieApplication::log()->error($e->getMessage());

            return Response::NOT_ACCEPTABLE;
        }
    }

    public function view(Request $request, User $user)
    {
        $this->conversation->newMessagesSince($user);

        return $this->conversation;
    }

    public function delete(Request $request, User $user)
    {
        if (!$this->conversation instanceof GroupConversationInterface) {
            return Response::BAD_REQUEST;
        }

        if (!$this->conversation->canManage($user)) {
            return Response::FORBIDDEN;
        }

        $this->conversation->delete();

        return Response::OK;
    }

    public function find(Request $request, User $user)
    {
        $object = $this->objectFromType($request);

        if (!$object) {
            return Response::BAD_REQUEST;
        }

        try {
            $conversation = AngieApplication::getContainer()
                ->get(ConversationResolverInterface::class)
                ->getConversation($user, $object);

            if (!$conversation) {
                return Response::NOT_FOUND;
            }

            return $conversation;
        } catch (LogicException $e) {
            AngieApplication::log()->error($e->getMessage());

            return Response::FORBIDDEN;
        } catch (Exception $e) {
            AngieApplication::log()->error($e->getMessage());

            return Response::NOT_ACCEPTABLE;
        }
    }

    private function objectFromType(Request $request): ?DataObject
    {
        $parent_type = Angie\Inflector::camelize(
            str_replace(
                '-',
                '_',
                (string) $request->get('type')
            )
        );

        $parent_id = $request->getId();

        return class_exists($parent_type) && is_subclass_of($parent_type, DataObject::class)
            ? DataObjectPool::get($parent_type, $parent_id)
            : null;
    }

    public function user_conversations(Request $request, User $user)
    {
        return ConversationUsers::prepareCollection('additional_user_conversations', $user);
    }

    public function attachments(Request $request, User $user)
    {
        return $this->conversation->getAttachments($user);
    }
}
