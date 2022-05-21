<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageCreatedEvent;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ActiveCollab\Module\System\Model\Message\MessageInterface;
use ActiveCollab\Module\System\Model\Message\SystemMessageInterface;
use ActiveCollab\Module\System\Model\Message\UserMessage;
use ActiveCollab\Module\System\Model\Message\UserMessageInterface;
use LogicException;
use RuntimeException;
use User;

class ConversationMessageFactory implements ConversationMessageFactoryInterface
{
    private $object_pool_announce;
    private $message_creator;

    public function __construct(
        callable $object_pool_announce,
        callable $message_creator
    )
    {
        $this->object_pool_announce = $object_pool_announce;
        $this->message_creator = $message_creator;
    }

    public function createUserMessage(
        ConversationInterface $conversation,
        User $user,
        string $body,
        array $attachments = [],
        ?int $order_id = null
    ): UserMessageInterface
    {
        if (empty($body) && empty($attachments)) {
            throw new LogicException('Message should has at least body or attachment.');
        }

        return $this->create(UserMessage::class, $conversation, $user, $body, $attachments, null, $order_id);
    }

    public function createSystemMessage(
        string $system_message,
        GroupConversationInterface $conversation,
        User $user,
        array $additional_data = []
    ): SystemMessageInterface
    {
        if (!in_array($system_message, SystemMessageInterface::SYSTEM_MESSAGES)) {
            throw new RuntimeException(
                sprintf(
                    "System message '%s' does not exist.",
                    $system_message
                )
            );
        }

        return $this->create($system_message, $conversation, $user, null, null, $additional_data);
    }

    private function create(
        string $type,
        ConversationInterface $conversation,
        User $user,
        ?string $body = null,
        ?array $attachments = null,
        ?array $additional_data = null,
        ?int $order_id = null
    ): MessageInterface
    {
        $message = call_user_func(
            $this->message_creator,
            [
                'type' => $type,
                'order_id' => $order_id,
                'conversation_id' => $conversation->getId(),
                'body' => $body ?: null,
                'attach_uploaded_files' => $attachments,
                'created_by_id' => $user->getId(),
                'created_by_name' => $user->getName(),
                'created_by_email' => $user->getEmail(),
                'raw_additional_properties' => $additional_data ? serialize($additional_data) : null,
            ]
        );

        call_user_func($this->object_pool_announce, new MessageCreatedEvent($message));

        return $message;
    }
}
