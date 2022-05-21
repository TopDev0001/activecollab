<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Mailer\Decorator;

use DataObject;
use IUser;

abstract class Decorator
{
    public function decorateSubject(string $subject): string
    {
        return mb_substr($subject, 0, 150);
    }

    public function decorateBody(
        IUser $recipient,
        string $subject,
        string $body,
        DataObject $context = null,
        string $unsubscribe_url = null,
        string $unsubscribe_label = null,
        bool $supports_go_to_action = false
    ): string
    {
        return sprintf('%s%s%s',
            $this->renderHeader($recipient, $subject, $context, $supports_go_to_action),
            $body,
            $this->renderFooter($recipient, $context, $unsubscribe_url, $unsubscribe_label)
        );
    }

    abstract protected function renderHeader(
        IUser $recipient,
        string $subject,
        DataObject $context = null,
        bool $supports_go_to_action = false
    ): string;

    abstract protected function renderFooter(
        IUser $recipient,
        DataObject $context = null,
        string $unsubscribe_url = '',
        string $unsubscribe_label = ''
    ): string;
}
