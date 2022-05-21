<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageDeletedEvent;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Utils\Conversations\MessageOrderIdResolverInterface;

abstract class Message extends BaseMessage
{
    public function includeBodyModeInJson(): bool
    {
        return false;
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'conversation_id' => $this->getConversationId(),
                'changed_on' => $this->getChangedOn(),
                'order_id' => $this->getOrderId(),
            ]
        );
    }

    public function whoCanSeeThis(): array
    {
        return $this->getConversation()->whoCanSeeThis();
    }

    public function getConversation(): ConversationInterface
    {
        return DataObjectPool::get(Conversation::class, $this->getConversationId());
    }

    public function includePlainTextBodyInJson(): bool
    {
        return true;
    }

    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function canEdit(User $user): bool
    {
        return $this->getCreatedById() === $user->getId();
    }

    public function canView(User $user): bool
    {
        return $this->getConversation()->isMember($user);
    }

    public function delete($bulk = false): void
    {
        DataObjectPool::announce(new MessageDeletedEvent($this));

        $this->getConversation()->touch();

        parent::delete($bulk);
    }

    public function validate(ValidationErrors &$errors)
    {
        if ($this->isNew()) {
            $this->setFieldValue(
                'order_id',
                AngieApplication::getContainer()
                    ->get(MessageOrderIdResolverInterface::class)
                    ->resolve($this->getFieldValue('order_id'))
            );
        }

        parent::validate($errors);
    }
}
