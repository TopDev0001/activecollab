<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs\Push;

use ActiveCollab\ActiveCollabJobs\Jobs\Job;
use ActiveCollab\ActiveCollabJobs\Utils\PushMessagingServiceInterface;
use InvalidArgumentException;
use Kreait\Firebase\Messaging\RawMessageFromArray;

class SilentPushNotificationJob extends Job
{
    public function __construct(array $data = null)
    {
        if (!isset($data['badge']) || !isset($data['device_tokens'])) {
            throw new InvalidArgumentException("'badge' and 'device_tokens' properties are required");
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $message = new RawMessageFromArray([
            'data' => [
                'type' => 'silent',
                'badge' => (string) $this->getData('badge'),
            ],
            'apns' => [
                // https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#apnsconfig
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'badge' => $this->getData('badge'),
                        'content-available' => 1,
                    ],
                ],
            ],
        ]);

        return $this->getContainer()
            ->get(PushMessagingServiceInterface::class)
            ->sendMulticast(
                $message,
                (array) $this->getData('device_tokens')
            );
    }
}
