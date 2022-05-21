<?php

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs\Push;

use ActiveCollab\ActiveCollabJobs\Jobs\Job;
use ActiveCollab\ActiveCollabJobs\Utils\PushMessagingServiceInterface;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationJob extends Job
{
    public function __construct(array $data = null)
    {
        if (!isset($data['title']) || !isset($data['device_tokens'])) {
            throw new \InvalidArgumentException("'title' and 'device_tokens' property is required");
        }
        parent::__construct($data);
    }

    public function execute()
    {
        if (
            $this->getContainer()->has(
                PushMessagingServiceInterface::class
            )) {
            /** @var PushMessagingServiceInterface $service */
            $service = $this->getContainer()->get(PushMessagingServiceInterface::class);
            $job_data =  $this->getData();
            $device_tokens = $job_data['device_tokens'] ?? null;
            $title = $job_data['title'] ?? null;
            $body = $job_data['body'] ?? null;
            $data = $job_data['data'] ?? [];
            $raw_messages = $job_data['raw_messages'] ?? null;

            if ($device_tokens) {
                $message = CloudMessage::new();
                $notification = Notification::create($title, $body);

                $message = $message->withNotification($notification)
                    ->withData($data)
                    ->withDefaultSounds();

                if (isset($job_data['badge'])) {
                    $message = $message->withApnsConfig(ApnsConfig::new()->withBadge($job_data['badge'])->withDefaultSound());
                }


                return $service->sendMulticast($message, $device_tokens);
            }
        }
        return false;
    }
}
