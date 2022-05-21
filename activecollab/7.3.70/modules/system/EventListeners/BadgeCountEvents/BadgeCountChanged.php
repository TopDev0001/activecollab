<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\BadgeCountEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\NotificationRecipientEvents\NotificationRecipientDeletedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\NotificationRecipientEvents\NotificationRecipientUpdatedEvent;
use ActiveCollab\Module\System\Model\Conversation\OwnConversation;
use ActiveCollab\Module\System\Model\Message\UserMessageInterface;
use ActiveCollab\Module\System\Utils\Conversations\ChatMessagePushNotificationDispatcherInterface;
use ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler\UsersBadgeCountThrottlerInterface;
use Angie\FeatureFlags\FeatureFlagsInterface;
use NotificationRecipient;

class BadgeCountChanged implements EventInterface
{
    private ChatMessagePushNotificationDispatcherInterface $notification_dispatcher;
    private UsersBadgeCountThrottlerInterface $badge_count_throttler;
    private FeatureFlagsInterface $feature_flags;

    public function __construct(
        ChatMessagePushNotificationDispatcherInterface $notification_dispatcher,
        UsersBadgeCountThrottlerInterface $badge_count_throttler,
        FeatureFlagsInterface $feature_flags
    ) {
        $this->notification_dispatcher = $notification_dispatcher;
        $this->badge_count_throttler = $badge_count_throttler;
        $this->feature_flags = $feature_flags;
    }

    public function __invoke(BadgeCountChangedEventInterface $event): void
    {
        if (!$this->feature_flags->isEnabled('push_notifications_for_chat')) {
            return;
        }

        $object = $event->getObject();

        if ($object instanceof UserMessageInterface && !$object->getConversation() instanceof OwnConversation) {
            $this->notification_dispatcher->dispatch($object, true);
        } elseif ($object instanceof NotificationRecipient
            && $this->shouldNotifyAboutBadgeCountChange($object, $event)
        ) {
            $this->badge_count_throttler->throttle($object->whoCanSeeThis());
        }
    }

    private function shouldNotifyAboutBadgeCountChange(
        NotificationRecipient $object,
        BadgeCountChangedEventInterface $event
    ): bool
    {
        return ($event instanceof NotificationRecipientDeletedEvent && !$object->isReadByRecipient())
            || ($event instanceof NotificationRecipientUpdatedEvent && $object->isReadByRecipient());
    }
}
