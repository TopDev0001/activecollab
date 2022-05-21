<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserDeletedEvent;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;

class ConversationUser extends BaseConversationUser
{
    public function whoCanSeeThis()
    {
        return [$this->getUserId()];
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'conversation_id' => $this->getConversationId(),
            'user_id' => $this->getUserId(),
            'new_messages_since' => $this->getNewMessagesSince(),
            'is_muted' => $this->getIsMuted(),
            'is_original_muted' => $this->getIsOriginalMuted(),
            'created_on' => $this->getCreatedOn(),
            'updated_on' => $this->getUpdatedOn(),
        ];
    }

    public function validate(ValidationErrors & $errors)
    {
        if (!$this->isNew() && $this->validatePresenceOf('user_id') && $this->isModifiedField('user_id')) {
            $errors->addError('Conversation can not be set to another user', 'user_id');
        }

        if (!$this->isNew() && $this->validatePresenceOf('conversation_id') && $this->isModifiedField('conversation_id')) {
            $errors->addError('Conversation can not be changed', 'conversation_id');
        }

        parent::validate($errors);
    }

    public function delete($bulk = false): void
    {
        DataObjectPool::announce(new ConversationUserDeletedEvent($this));

        parent::delete($bulk);
    }

    public function getConversation(): ConversationInterface
    {
        return DataObjectPool::get(Conversation::class, $this->getConversationId());
    }

    public function getUser(): User
    {
        return DataOBjectPool::get(User::class, $this->getUserId());
    }
}
