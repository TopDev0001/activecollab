<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Notifications\TemplatePathResolver;

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannelInterface;
use ActiveCollab\Foundation\Notifications\NotificationInterface;

interface NotificationTemplatePathResolverInterface
{
    public function getNotificationTemplatePath(
        NotificationInterface $notification,
        NotificationChannelInterface $channel
    ): string;
}
