<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\MessageEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageCreatedEventInterface;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GeneralConversation;
use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use ActiveCollab\Module\System\Model\Conversation\OneToOneConversation;
use ActiveCollab\Module\System\Model\Conversation\OwnConversation;
use ActiveCollab\Module\System\Model\Conversation\ParentObjectConversation;
use ActiveCollab\Module\System\Model\Message\UserMessageInterface;
use ActiveCollab\Module\System\Utils\Conversations\ChatMessagePushNotificationDispatcherInterface;
use ActiveCollab\Module\System\Utils\Conversations\MessageMentionResolverInterface;
use Conversation;
use Message;

class MessageCreated implements EventInterface
{
    private LoggerInterface $logger;
    private MessageMentionResolverInterface $mention_resolver;
    private ChatMessagePushNotificationDispatcherInterface $notification_dispatcher;

    public function __construct(
        MessageMentionResolverInterface $mention_resolver,
        ChatMessagePushNotificationDispatcherInterface $notification_dispatcher,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->mention_resolver = $mention_resolver;
        $this->notification_dispatcher = $notification_dispatcher;
    }

    public function __invoke(MessageCreatedEventInterface $event)
    {
        /** @var Message $message */
        $message = $event->getObject();

        /** @var Conversation $conversation */
        $conversation = $message->getConversation();

        $this->logger->info(
            'Chat message posted.',
            [
                'conversation_type' => $this->resolveShortType($conversation),
            ]
        );

        if ($message->getNewMentions()) {
            $this->mention_resolver->resolve($message);
        }

        if ($message instanceof UserMessageInterface) {
            $conversation->newMessagesSince($message->getCreatedBy());
        }

        $conversation->setLastMessageOn($message->getCreatedOn());
        $conversation->touch();

        if (!$conversation instanceof OwnConversation && $message instanceof UserMessageInterface) {
            $this->notification_dispatcher->dispatch($message);
        }
    }

    private function resolveShortType(ConversationInterface $conversation): string
    {
        if ($conversation instanceof GeneralConversation) {
            return 'general';
        } elseif ($conversation instanceof ParentObjectConversation) {
            return strtolower($conversation->getParentType());
        } elseif ($conversation instanceof OwnConversation) {
            return 'own';
        } elseif ($conversation instanceof OneToOneConversation) {
            return 'one-to-one';
        } elseif ($conversation instanceof GroupConversation) {
            return 'group';
        }

        return $conversation->getType();
    }
}
