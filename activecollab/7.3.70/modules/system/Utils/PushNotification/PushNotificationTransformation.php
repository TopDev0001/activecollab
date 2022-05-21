<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\PushNotification;

use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GeneralConversation;
use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use ActiveCollab\Module\System\Model\Conversation\ParentObjectConversation;
use ActiveCollab\Module\System\Model\Message\MessageInterface;
use Message;

class PushNotificationTransformation
{
    public static function transformTitleForMessage(MessageInterface $message, ConversationInterface $conversation): string
    {
        $title = $message->getCreatedByName();

        if (
            (
                $conversation->getType() === ParentObjectConversation::class ||
                $conversation->getType() === GroupConversation::class
            ) &&
            $conversation->getName()
        ){
            return $message->getCreatedByName().' in '.$conversation->getName().':';
        }

        if ($conversation->getType() === GeneralConversation::class){
            return $message->getCreatedByName().' in General:';
        }

        return $title;
    }

    public static function transformBodyForMessage(Message $message): string
    {
        $body = $message->getPlainTextBody();
        if (strlen($body) === 0 && $message instanceof \IAttachments && count($message->getAttachments()) > 0) {
            $body = 'Sent an attachment';
        }

        return $body;
    }
}
