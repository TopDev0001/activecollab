<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Message\UserMessage;

use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationScheduleMatcherInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationTransformation;
use ActiveCollab\Module\System\Utils\PushNotificationJobDispatcher\PushNotificationJobDispatcherInterface;
use Message;

class PushNotificationUserMessageService extends UserMessageService implements PushNotificationUserMessageServiceInterface
{
    private $muted_users_in_conversation_finder;
    private PushNotificationScheduleMatcherInterface $schedule_matcher;
    private PushNotificationJobDispatcherInterface $push_notification_job_dispatcher;

    public function __construct(
        PushNotificationJobDispatcherInterface $push_notification_job_dispatcher,
        PushNotificationScheduleMatcherInterface $schedule_matcher,
        callable $muted_users_in_conversation_finder
    ) {
        $this->muted_users_in_conversation_finder = $muted_users_in_conversation_finder;
        $this->push_notification_job_dispatcher = $push_notification_job_dispatcher;
        $this->schedule_matcher = $schedule_matcher;
    }

    public function send(Message $message): void
    {
        $conversation = $message->getConversation();
        $user_ids = $this->getUserToNotify($conversation, $message);

        if (!count($user_ids)) {
            return;
        }
        $title = PushNotificationTransformation::transformTitleForMessage($message, $conversation);
        $body = PushNotificationTransformation::transformBodyForMessage($message);

        $this
            ->push_notification_job_dispatcher
            ->dispatchForUsers(
                $user_ids,
                $title,
                $body,
                [
                    'id' => $message->getId(),
                    'class' => $message->getType(),
                    'conversation_id' => $message->getConversationId(),
                    'conversation_class' => $conversation->getType(),
                    'url_path' => $conversation->getUrlPath(),
                ]
            );
    }

    /**
     * Get user ids which are not muted and not absent.
     */
    private function getUserToNotify(ConversationInterface $conversation, Message $message): array
    {
        $user_ids = array_values(
            array_diff(
                $conversation->getMemberIds(),
                call_user_func(
                    $this->muted_users_in_conversation_finder,
                    $conversation
                ),
                [$message->getCreatedById()]
            )
        );
        if (count($user_ids) > 0){
            //include only user ids that match schedule
            $user_ids = $this->schedule_matcher->match($user_ids);
        }

        return $user_ids;
    }
}
