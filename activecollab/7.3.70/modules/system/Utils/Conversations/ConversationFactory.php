<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationEvents\ConversationCreatedEvent;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\CustomConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\OneToOneConversation;
use ActiveCollab\Module\System\Model\Conversation\OwnConversation;
use ActiveCollab\Module\System\Model\Conversation\ParentObjectConversation;
use Angie\Inflector;
use Conversations;
use ConversationUsers;
use DataObject;
use DataObjectPool;
use IMembers;
use LogicException;
use RuntimeException;
use User;

class ConversationFactory implements ConversationFactoryInterface
{
    private ConversationResolverInterface $conversation_resolver;
    private $user_cache_cleaner;

    public function __construct(
        ConversationResolverInterface $conversation_resolver,
        callable $user_cache_cleaner
    )
    {
        $this->conversation_resolver = $conversation_resolver;
        $this->user_cache_cleaner = $user_cache_cleaner;
    }

    public function create(User $user, string $type, $value): ConversationInterface
    {
        if (in_array($type, ConversationInterface::CONVERSATION_TYPES)) {
            $conversation = $this->createCustomConversation($type, $user, (array) $value);
        } else {
            $conversation = $this->createSmartConversation($user, $type, $value);
        }

        DataObjectPool::announce(new ConversationCreatedEvent($conversation));

        return $conversation;
    }

    private function createCustomConversation(string $type, User $user, array $user_ids): CustomConversationInterface
    {
        if (!($conversation = $this->getExistingCustomConversation($type, [$user->getId(), ...$user_ids]))) {
            $set_default_admin = false;

            if ($type === ConversationInterface::CONVERSATION_TYPE_GROUP) {
                if ($this->shouldCreateOwnConversation($user, $user_ids)) {
                    throw new LogicException('Group conversation needs to have minimum two distinct members.');
                }

                $conversation = Conversations::create(
                    [
                        'type' => GroupConversation::class,
                    ]
                );
                $set_default_admin = true;
            } elseif ($this->shouldCreateOwnConversation($user, $user_ids)) {
                $conversation = Conversations::create(['type' => OwnConversation::class]);
            } elseif ($this->shouldCreateOneToOneConversation($user, $user_ids)) {
                $conversation = Conversations::create(['type' => OneToOneConversation::class]);
            } else {
                $conversation = Conversations::create(['type' => GroupConversation::class]);
                $set_default_admin = true;
            }

            $user_ids = array_unique(
                array_merge(
                    [$user->getId()],
                    $user_ids
                )
            );

            $conversation_users_data = [];
            foreach ($user_ids as $user_id) {
                $conversation_users_data[] = [
                    'conversation_id' => $conversation->getId(),
                    'user_id' => $user_id,
                    'is_admin' => $set_default_admin && $user_id === $user->getId(),
                ];
            }

            ConversationUsers::createMany($conversation_users_data);

            // clear cache of users to reset their 'visible_users' cached value
            if ($conversation instanceof GroupConversationInterface) {
                call_user_func($this->user_cache_cleaner, $user_ids);
            }
        }

        return $conversation;
    }

    private function getExistingCustomConversation(string $type, array $user_ids): ?CustomConversationInterface
    {
        return $type === ConversationInterface::CONVERSATION_TYPE_CUSTOM
            ? $this->conversation_resolver->getCustomConversation(
                $user_ids,
                count($user_ids) === 2
                    ? OneToOneConversation::class
                    : OwnConversation::class
            )
            : null;
    }

    private function createSmartConversation(User $user, string $type, $value): ConversationInterface
    {
        $parent_type = ucfirst(Inflector::camelize(str_replace('-', '_', $type)));

        if (!class_exists($parent_type) || !is_subclass_of($parent_type, DataObject::class)) {
            throw new RuntimeException("Type '{$type}' is not valid for creating a conversation.");
        }

        $object = DataObjectPool::get($parent_type, (int) $value);

        if (!$object || !($object instanceof IMembers)) {
            throw new RuntimeException("Object for type '{$type}' is not valid for creating a conversation.");
        }

        if (!$object->isMember($user)) {
            throw new LogicException("User #{$user->getId()} is not member of this object.");
        }

        if ($conversation = $this->conversation_resolver->getConversation($user, $object)) {
            return $conversation;
        } else {
            return Conversations::create(
                [
                    'type' => ParentObjectConversation::class,
                    'name' => $object->getName(),
                    'parent_type' => get_class($object),
                    'parent_id' => $object->getId(),
                ]
            );
        }
    }

    private function shouldCreateOwnConversation(User $user, array $user_ids): bool
    {
        return count($user_ids) === 1 && in_array($user->getId(), $user_ids);
    }

    private function shouldCreateOneToOneConversation(User $user, array $user_ids): bool
    {
        return count($user_ids) === 1 && !in_array($user->getId(), $user_ids);
    }
}
