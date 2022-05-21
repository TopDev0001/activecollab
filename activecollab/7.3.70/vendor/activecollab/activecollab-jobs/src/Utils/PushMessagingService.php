<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils;


use Kreait\Firebase\Contract\Messaging;

class PushMessagingService implements PushMessagingServiceInterface
{
    /**
     * @var Messaging
     */
    private $messaging;

    public function __construct($messaging)
    {
        $this->messaging = $messaging;
    }

    public function send($message)
    {
        $this->messaging->send($message);
    }

    public function sendMulticast($message, array $device_tokens)
    {
        return $this->messaging->sendMulticast($message, $device_tokens);
    }

    public function getMessaging(): Messaging
    {
        return $this->messaging;
    }


    public function sendAll(array $messages)
    {
        return $this->messaging->sendAll($messages);
    }
}
