<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs\Http;

use ActiveCollab\ActiveCollabJobs\Jobs\Job;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\ExceptionWebhookDispatchResult;
use InvalidArgumentException;

class SendWebhook extends Job
{
    const DEFAULT_METHOD = 'POST';
    const DEFAULT_VERIFY = true;
    const DEFAULT_TIMEOUT = 3;

    public function __construct(array $data)
    {
        if (empty($data['webhook_id']) || (int) $data['webhook_id'] < 1) {
            throw new InvalidArgumentException('Webhook ID is required');
        }

        if (!is_int($data['webhook_id'])) {
            $data['webhook_id'] = (int) $data['webhook_id'];
        }

        if (empty($data['event_type'])) {
            throw new InvalidArgumentException('Event type is required');
        }

        if (empty($data['return_url']) || empty($data['return_secret'])) {
            $data['return_url'] = '';
            $data['return_secret'] = '';
        }

        if ($data['return_url'] && !filter_var($data['return_url'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Return URL is not a valid URL');
        }

        if (!isset($data['verify']) || !is_bool($data['verify'])) {
            $data['verify'] = self::DEFAULT_VERIFY;
        }

        if (empty($data['url']) || !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Valid URL is required');
        }

        if (empty($data['payload'])) {
            throw new InvalidArgumentException('Payload is required');
        }

        if (empty($data['headers'])) {
            $data['headers'] = [];
        }

        if (!$this->isValidTimeout($data)) {
            $data['timeout'] = null;
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $result = $this->webhooks_dispatcher->dispatch(
            $this->getInstanceId(),
            $this->getWebhookId(),
            $this->getData('url'),
            $this->getData('headers'),
            $this->getData('payload'),
            $this->resolveTimeout($this->getData('timeout')),
            (bool) $this->getData('verify')
        );

        $this->webhooks_health_manager->notify(
            $this->getInstanceId(),
            $this->getWebhookId(),
            $this->getPriority(),
            $result
        );

        if ($result instanceof ExceptionWebhookDispatchResult) {
            throw $result->getException();
        }
    }

    private function isValidTimeout(array $data): bool
    {
        return array_key_exists('timeout', $data)
            && is_int($data['timeout'])
            && $data['timeout'] > 0;
    }

    private function resolveTimeout(?int $timeout): int
    {
        if ($timeout === null || $timeout <= 0) {
            $timeout = (int) getenv('ACTIVECOLLAB_JOB_CONSUMER_WEBHOOK_TIMEOUT');

            if ($timeout <= 0) {
                $timeout = self::DEFAULT_TIMEOUT;
            }
        }

        return $timeout;
    }

    private function getWebhookId(): int
    {
        return (int) $this->getData('webhook_id');
    }
}
