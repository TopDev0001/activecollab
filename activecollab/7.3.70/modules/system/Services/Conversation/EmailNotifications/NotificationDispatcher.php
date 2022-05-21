<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use Angie\Notifications\NotificationsInterface;
use IUser;

class NotificationDispatcher implements NotificationDispatcherInterface
{
    private NotificationsInterface $notifications;
    private RootUrlInterface $root_url;

    public function __construct(
        NotificationsInterface $notifications,
        RootUrlInterface $root_url
    ) {
        $this->notifications = $notifications;
        $this->root_url = $root_url;
    }

    public function notify(IUser $user, UnreadConversationsDataInterface $notification_data): void
    {
        if ($notification_data->getTotal() > 0) {
            $this->notifications
                ->notifyAbout('system/unread_messages')
                ->setTotal($notification_data->getTotal())
                ->setApplicationUrl($this->root_url->getUrl())
                ->setMessagesByConversation($notification_data->getBreakdown())
                ->sendToUsers([$user]);
        }
    }
}
