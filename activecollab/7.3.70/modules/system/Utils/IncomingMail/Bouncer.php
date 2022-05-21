<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\IncomingMail;

use ActiveCollab\Foundation\Mail\Incoming\Message\MessageInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Bouncer\BouncerInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\BounceInterface;
use Angie\Notifications\NotificationsInterface;
use AnonymousUser;
use BounceEmailNotification;
use Psr\Log\LoggerInterface;

class Bouncer implements BouncerInterface
{
    private NotificationsInterface $notifications;
    private LoggerInterface $logger;

    public function __construct(
        NotificationsInterface $notifications,
        LoggerInterface $logger
    )
    {
        $this->notifications = $notifications;
        $this->logger = $logger;
    }

    public function bounce(MessageInterface $message, BounceInterface $bounce): void
    {
        $this->logger->info(
            'Email import: Message bounced',
            [
                'sender' => $message->getSender(),
                'subject' => $message->getSubject(),
                'reason' => $bounce->getReason(),
            ]
        );

        /** @var BounceEmailNotification $notification */
        $notification = $this->notifications->notifyAbout('system/bounce_email');
        $notification
            ->setBounceReason($bounce->getReason())
            ->sendToUsers(
                [
                    new AnonymousUser(null, $message->getSender()),
                ]
            );
    }
}
