<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use ActiveCollab\Module\System\Utils\InactiveUsersResolver\InactiveUsersResolverInterface;
use DateTimeValue;

class UsersToNotifyAboutUnreadMessagesResolver implements UsersToNotifyAboutUnreadMessagesResolverInterface
{
    private ConversationEmailNotificationSettingsResolverInterface $email_notification_settings_resolver;
    private InactiveUsersResolverInterface $inactive_users_resolver;
    private $user_ids_with_new_messages_resolver;

    public function __construct(
        ConversationEmailNotificationSettingsResolverInterface $email_notification_settings_resolver,
        InactiveUsersResolverInterface $inactive_users_resolver,
        callable $user_ids_with_new_messages_resolver
    ) {
        $this->email_notification_settings_resolver = $email_notification_settings_resolver;
        $this->inactive_users_resolver = $inactive_users_resolver;
        $this->user_ids_with_new_messages_resolver = $user_ids_with_new_messages_resolver;
    }

    public function getUserIds(DateTimeValue $current_time): array
    {
        $user_ids = array_diff(
            $this->inactive_users_resolver->getInactiveUsersIds(
                $current_time,
                UsersToNotifyAboutUnreadMessagesResolverInterface::USERS_INACTIVE_FOR_30_MINUTES
            ),
            $this->email_notification_settings_resolver->getUserIdsWithDisabledEmailNotifications()
        );

        if (empty($user_ids)) {
            return [];
        }

        return array_intersect(
            $user_ids,
            call_user_func(
                $this->user_ids_with_new_messages_resolver,
                $current_time,
                UsersToNotifyAboutUnreadMessagesResolverInterface::MESSAGES_OLDER_THEN_90_MINUTES
            )
        );
    }
}
