<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation;

use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationNotificationDataFactoryInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\NotificationDispatcherInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\UsersToNotifyAboutUnreadMessagesResolverInterface;
use ActiveCollab\Module\System\Utils\InactiveUsersResolver\InactiveUsersResolverInterface;
use DateTimeValue;
use IUser;

class NotifyUserAboutUnreadMessagesService implements NotifyUserAboutUnreadMessagesServiceInterface
{
    private $is_enabled_notification;
    private InactiveUsersResolverInterface $inactive_users_resolver;
    private NotificationDispatcherInterface $dispatcher;
    private ConversationNotificationDataFactoryInterface $factory;
    private LoggerInterface $logger;

    public function __construct(
        callable $is_enabled_notification,
        InactiveUsersResolverInterface $inactive_users_resolver,
        NotificationDispatcherInterface $dispatcher,
        ConversationNotificationDataFactoryInterface $factory,
        LoggerInterface $logger
    ) {
        $this->is_enabled_notification = $is_enabled_notification;
        $this->inactive_users_resolver = $inactive_users_resolver;
        $this->dispatcher = $dispatcher;
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function notify(IUser $user, DateTimeValue $current_time): void
    {
        if ($this->shouldNotify($user, $current_time)) {
            $data = $this->factory->produceDataForUser(
                $user,
                $current_time->advance(
                    -1 * UsersToNotifyAboutUnreadMessagesResolverInterface::MESSAGES_OLDER_THEN_90_MINUTES,
                    false
                )
            );

            if ($data->getTotal()) {
                $this->dispatcher->notify($user, $data);
            } else {
                $this->logger->info(
                    'Skip notify user about unread messages because there is no unread messages.',
                    [
                        'user_id' => $user->getId(),
                    ]
                );
            }
        } else {
            $this->logger->info(
                'Skip notify user about unread messages because notification is disabled or user become active.',
                [
                    'user_id' => $user->getId(),
                ]
            );
        }
    }

    private function shouldNotify(IUser $user, DateTimeValue $current_time): bool
    {
        return $user->isActive() &&
            !$user->isClient() &&
            call_user_func($this->is_enabled_notification, $user) &&
            $this->inactive_users_resolver->isUserInactive(
                $user,
                UsersToNotifyAboutUnreadMessagesResolverInterface::USERS_INACTIVE_FOR_30_MINUTES,
                $current_time
            );
    }
}
