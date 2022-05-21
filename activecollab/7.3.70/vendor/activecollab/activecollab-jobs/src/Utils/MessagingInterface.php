<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils;

use Kreait\Firebase\Messaging\Message;

/**
 * This interface is to describe Messaging class
 * Interface MessagingInterface.
 * @package ActiveCollab\ActiveCollabJobs\Utils
 */
interface MessagingInterface
{
    /**
     * @param  Message|array<string, mixed> $message
     * @return mixed
     */
    public function send($message);

    /**
     * @param  Message|array<string, mixed> $message
     * @param  array                        $device_tokens
     * @return mixed
     */
    public function sendMulticast($message, array $device_tokens);
}
