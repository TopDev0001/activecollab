<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserUpdatedEvent;

/**
 * ConversationUsers class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ConversationUsers extends BaseConversationUsers
{
    public static function prepareCollection(string $collection_name, $user)
    {
        if ($user->isClient()) {
            throw new InvalidParamError('user', $user, '$user cannot be client.');
        }

        if (str_starts_with($collection_name, 'additional_user_conversations')) {
            return self::prepareAdditionalUserConversationCollection($collection_name, $user);
        } else {
            throw new RuntimeException("Collection name '$collection_name' does not exist.");
        }
    }

    private static function prepareAdditionalUserConversationCollection(string $collection_name, User $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);
        $collection->setConditions('user_id = ?', $user->getId());

        return $collection;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): ConversationUser
    {
        $user_conversation = parent::update($instance, $attributes, $save);

        DataObjectPool::announce(new ConversationUserUpdatedEvent($user_conversation));

        return $user_conversation;
    }
}
