<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types = 1);

namespace ActiveCollab\ActiveCollabJobs\Utils;


use Kreait\Firebase\Contract\Messaging;

interface PushMessagingServiceInterface extends MessagingInterface
{
    public function getMessaging(): Messaging;

    /**
     * @param Messaging\Message[] $messages
     * @throws \Kreait\Firebase\Exception\FirebaseException
     * @throws \Kreait\Firebase\Exception\MessagingException
     */
    public function sendAll(array $messages);
}
