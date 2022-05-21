<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationEvents\ConversationDeletedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserCreatedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserUpdatedEvent;
use ActiveCollab\Module\System\Model\Conversation\CustomConversationInterface;
use ActiveCollab\Module\System\Model\Message\UserMessageInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageFactoryInterface;

abstract class Conversation extends BaseConversation
{
    public function getMessages(User $user): RelativeCursorModelCollection
    {
        return Messages::prepareRelativeCursorCollection(
            sprintf(
                'conversation_messages_%s',
                $this->getId()
            ),
            $user
        );
    }

    public function getAttachments(User $user): CursorModelCollection
    {
        return Attachments::prepareCursorCollection(
            sprintf(
                'attachments_in_conversation_%s',
                $this->getId()
            ),
            $user
        );
    }

    public function createMessage(
        User $user,
        string $body,
        array $attachments = [],
        ?int $order_id = null
    ): UserMessageInterface
    {
        return AngieApplication::getContainer()
            ->get(ConversationMessageFactoryInterface::class)
            ->createUserMessage($this, $user, $body, $attachments, $order_id);
    }

    public function newMessagesSince(User $user, DateTimeValue $time = null): ConversationUser
    {
        $new_messages_since = $time ?? new DateTimeValue();
        $conversation_user = $this->getConversationUser($user);

        if (!$conversation_user) {
            $conversation_user = ConversationUsers::create(
                [
                    'user_id' => $user->getId(),
                    'conversation_id' => $this->getId(),
                    'new_messages_since' => $new_messages_since,
                ]
            );

            DataObjectPool::announce(new ConversationUserCreatedEvent($conversation_user));
        } else {
            $conversation_user->setNewMessagesSince($new_messages_since);

            $conversation_user = $this->muteConversationIfOriginalStatusIsMuted($conversation_user);

            $conversation_user->save();

            DataObjectPool::announce(new ConversationUserUpdatedEvent($conversation_user));
        }

        return $conversation_user;
    }

    public function getConversationUser(User $user): ?ConversationUser
    {
        /** @var ConversationUser $conversation_user */
        $conversation_user = ConversationUsers::findOneBy(
            [
                'user_id' => $user->getId(),
                'conversation_id' => $this->getId(),
            ]
        );

        if (!$conversation_user && $this instanceof CustomConversationInterface) {
            throw new LogicException("User #{$user->getId()} is not member of conversation #{$this->getId()}");
        }

        return $conversation_user;
    }

    public function hasMessages(): bool
    {
        return Messages::count(['conversation_id = ?', $this->getId()]) > 0;
    }

    public function canView(User $user): bool
    {
        return $this->isMember($user);
    }

    public function whoCanSeeThis(): array
    {
        return $this->getMemberIds();
    }

    public function getMemberIdsPreloadKey(): string
    {
        return Conversation::class;
    }

    protected function includeArchivedAndTrashedMembers(): bool
    {
        return false;
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'parent_type' => $this->getParentType(),
                'parent_id' => $this->getParentId(),
                'messages' => Messages::getByConversation($this),
            ]
        );
    }

    public function validate(ValidationErrors &$errors)
    {
        if (!$this->validatePresenceOf('type')) {
            $errors->addError('Type filed is required.', 'type');
        }

        parent::validate($errors);
    }

    public function getRoutingContext(): string
    {
        return 'conversations';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'id' => $this->getId(),
        ];
    }

    public function getUrlPath(): string
    {
        return '/conversations/' . $this->getId();
    }

    public function delete($bulk = false): void
    {
        try {
            DB::beginWork('Deleting conversation connection @ ' . __CLASS__);

            DataObjectPool::announce(new ConversationDeletedEvent($this));

            DB::execute('DELETE FROM messages WHERE conversation_id = ?', $this->getId());
            DB::execute('DELETE FROM conversation_users WHERE conversation_id = ?', $this->getId());

            parent::delete($bulk);

            DB::commit('Conversation connection deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete conversation connection @ ' . __CLASS__);

            throw $e;
        }
    }

    protected function muteConversationIfOriginalStatusIsMuted(ConversationUser $conversation_user): ConversationUser
    {
        if ($conversation_user->getIsOriginalMuted() && !$conversation_user->getIsMuted()) {
            $conversation_user->setIsMuted(true);
            $conversation_user->setIsOriginalMuted(false);
        }

        return $conversation_user;
    }
}
