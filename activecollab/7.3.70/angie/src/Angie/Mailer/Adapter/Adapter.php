<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Mailer\Adapter;

use Angie\Mailer;
use DataObject;
use IComments;
use IUser;

abstract class Adapter
{
    abstract public function send(
        IUser $sender,
        IUser $recipient,
        string $subject,
        string $body,
        DataObject $context = null,
        iterable $attachments = null,
        callable $on_sent = null
    ): int;

    protected function messageSent(
        IUser $sender,
        IUser $recipient,
        string $subject,
        string $body,
        DataObject $context = null,
        iterable $attachments = null,
        callable $on_sent = null
    ): int
    {
        if ($on_sent) {
            $from = $sender->getName() . ' <' . $sender->getEmail() . '>';
            $to = $recipient->getName() . ' <' . $recipient->getEmail() . '>';
            $reply_to = $this->routeReplyTo($sender, $recipient, $context);

            call_user_func($on_sent, $from, $to, $subject, $body, $reply_to);
        }

        return 1;
    }

    /**
     * Prepare reply to data based on input paramteres.
     *
     * - False means don't set Reply-To header
     * - String is an actual address
     * - Array is reply to context
     *
     * @return array|bool|string
     */
    protected function routeReplyTo(IUser $sender, IUser $recipient, DataObject $context = null)
    {
        if ($sender->getEmail() == $recipient->getEmail()
            || $sender->getEmail() == Mailer::getDefaultSender()->getEmail()
        ) {
            return false;
        }

        if ($context instanceof IComments && $context->canCommentViaEmail($recipient)) {
            return $context->getId() ? [get_class($context), $context->getId()] : false;
        }

        return $sender->getEmail();
    }
}
