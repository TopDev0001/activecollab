<?php

namespace ActiveCollab\ActiveCollabJobs\Jobs\Push;

use ActiveCollab\ActiveCollabJobs\Jobs\Job;
use ActiveCollab\ActiveCollabJobs\Utils\PushMessagingServiceInterface;
use Kreait\Firebase\Messaging\CloudMessage;

class RawPushNotificationJob extends Job
{
    public function __construct(array $data = null)
    {
        if (!isset($data['raw_messages']) || !is_iterable($data['raw_messages'])) {
            throw new \InvalidArgumentException("'raw_messages' property is required and must be array");
        }
        parent::__construct($data);
    }

    public function execute()
    {
        if (
            $this->getContainer()->has(
                PushMessagingServiceInterface::class
            )) {
            $service = $this->getContainer()->get(PushMessagingServiceInterface::class);
            $job_data =  $this->getData();
            $raw_messages = $job_data['raw_messages'];
            $messages = [];
            foreach ($raw_messages as $raw_message) {
                $messages[] = CloudMessage::fromArray($raw_message);
            }
            return $service->sendAll($messages);
        }
        return false;
    }
}
