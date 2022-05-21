<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Message\UserMessage;
use ActiveCollab\Module\System\Model\Message\UserMessageInterface;
use ActiveCollab\Module\System\Utils\MessagesTransformator\MessagesTransformatorInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;
use Angie\Http\ResponseInterface;

AngieApplication::useController('conversations', EnvironmentFramework::INJECT_INTO);

class ConversationMessagesController extends ConversationsController
{
    private ?Message $message = null;

    public function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($message_id = $request->getId('message_id')) {
            $this->message = DataObjectPool::get(Message::class, $message_id);

            if (!$this->message) {
                return Response::NOT_FOUND;
            }

            if (!($this->message instanceof UserMessageInterface)) {
                return Response::FORBIDDEN;
            }
        }

        return null;
    }

    public function create(Request $request, User $user)
    {
        $order_id = $request->post('order_id');

        return $this->conversation->createMessage(
            $user,
            (string) $request->post('body'),
            (array) $request->post('attach_uploaded_files'),
            $order_id ? (int) $order_id : null
        );
    }

    public function index(Request $request, User $user)
    {
        return $this->conversation->getMessages($user);
    }

    public function view(Request $request, User $user)
    {
        if (!$this->message->canView($user)) {
            return Response::FORBIDDEN;
        }

        return $this->message;
    }

    public function delete(Request $request, User $user)
    {
        if (!$this->message->canDelete($user)) {
            return Response::FORBIDDEN;
        }

        return Messages::scrap($this->message);
    }

    public function edit(Request $request, User $user)
    {
        if (!$this->message->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        try {
            return $this->message->update(
                (string) $request->put('body'),
                (array) $request->put('attach_uploaded_files'),
                (array) $request->put('drop_attached_files')
            );
        } catch (LogicException $e) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => $e->getMessage()]
            );
        }
    }

    public function mark_as_unread(Request $request, User $user)
    {
        if ($this->message->isCreatedBy($user) || !$this->message instanceof UserMessageInterface) {
            return ResponseInterface::FORBIDDEN;
        }

        $this->message->markAsUnreadFor($user);

        return ResponseInterface::OK;
    }

    public function create_task(Request $request, User $user)
    {
        $message_ids = (array) $request->post('message_ids');
        $project_id = $request->post('project_id');

        if (empty($project_id) || empty($message_ids)) {
            return Response::INVALID_PROPERTIES;
        }

        /** @var Project $project */
        $project = Projects::findById($project_id);

        if (!$project instanceof Project) {
            return Response::NOT_FOUND;
        }

        if (!$project->canView($user)) {
            return Response::FORBIDDEN;
        }

        $messages = Messages::find([
            'conditions' => [
                'type = ? AND conversation_id = ? AND id IN (?)',
                UserMessage::class,
                $this->conversation->getId(),
                $message_ids,
            ],
            'order' => 'order_id ASC',
        ]);

        $messages = $messages ? $messages->toArray() : null;

        if (empty($messages)) {
            return Response::NOT_FOUND;
        }

        try {
            return AngieApplication::getContainer()
                ->get(MessagesTransformatorInterface::class)
                ->transform(
                    $project,
                    $user,
                    Task::class,
                    ...$messages,
                );
        } catch (Throwable $exception) {
            AngieApplication::log()->error(
                'Failed to convert messages to task',
                [
                    'exception_message' => $exception->getMessage(),
                    'messages_ids' => $message_ids,
                    'conversation_id' => $this->conversation->getId(),
                ]
            );

            return new StatusResponse(
                Response::BAD_REQUEST,
                lang('Something went wrong. Please try again or contact customer support.')
            );
        }
    }
}
