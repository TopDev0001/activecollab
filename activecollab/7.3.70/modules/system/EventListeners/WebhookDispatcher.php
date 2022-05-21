<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners;

use ActiveCollab\ActiveCollabJobs\Jobs\Http\SendWebhook;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Foundation\Events\EventInterface;
use ActiveCollab\Foundation\Events\WebhookEvent\WebhookEventInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\OnDemand\Utils\ShepherdIntegration\ShepherdWebhook;
use Exception;
use Webhook;
use WebhooksIntegration;

class WebhookDispatcher implements WebhookDispatcherInterface
{
    private $enabled_webhooks_resolver;
    private JobsDispatcherInterface $jobs;
    private AccountIdResolverInterface $account_id_resolver;
    private LoggerInterface $logger;

    public function __construct(
        callable $enabled_webhooks_resolver,
        JobsDispatcherInterface $jobs,
        AccountIdResolverInterface $account_id_resolver,
        LoggerInterface $logger
    )
    {
        $this->enabled_webhooks_resolver = $enabled_webhooks_resolver;
        $this->jobs = $jobs;
        $this->logger = $logger;
        $this->account_id_resolver = $account_id_resolver;
    }

    public function __invoke(EventInterface $event)
    {
        if (!$event instanceof WebhookEventInterface) {
            return;
        }

        $webhooks = $this->getEnabledWebhooks();

        if (empty($webhooks)) {
            $this->logger->info(
                "Skipping '{event_type}' webhook dispatch. {reason}.",
                [
                    'event_type' => $event->getWebhookEventType(),
                    'reason' => 'No enabled webhooks',
                ]
            );

            return;
        }

        foreach ($webhooks as $webhook) {
            if (!$webhook->filterEvent($event)) {
                $this->logger->info(
                    "Skipping '{event_type}' webhook dispatch. {reason}.",
                    [
                        'event_type' => $event->getWebhookEventType(),
                        'context' => $event->getWebhookContext(),
                        'url' => $webhook->getUrl(),
                        'reason' => 'Event filtered out due to webhook filter settings',
                    ]
                );
                continue;
            }

            $payload = $event->getWebhookPayload($webhook);

            if (empty($payload)) {
                $this->logger->info(
                    "Skipping '{event_type}' webhook dispatch. {reason}.",
                    [
                        'event_type' => $event->getWebhookEventType(),
                        'url' => $webhook->getUrl(),
                        'reason' => 'Payload is empty',
                    ]
                );

                continue;
            }

            $url = $webhook->getUrl();
            $custom_query_params = $webhook->getCustomQueryParams($event);

            if (!empty($custom_query_params)) {
                $url .= parse_url($url, PHP_URL_QUERY) ? '&' : '?';
                $url .= $custom_query_params;
            }

            //@ToDo switch to version based payload
            if ($webhook instanceof ShepherdWebhook) {
                $payload = [
                    'instance_id' => $this->account_id_resolver->getAccountId(),
                    'type' => $event->getWebhookEventType(),
                    'timestamp' => (new DateTimeValue())->getTimestamp(),
                    'payload' => $payload,
                ];
            }

            $custom_headers = $webhook->getCustomHeaders($event);

            try {
                $this->jobs->dispatch(
                    new SendWebhook(
                        [
                            'instance_id' => $this->account_id_resolver->getAccountId(),
                            'webhook_id' => $webhook->getId(),
                            'event_type' => $event->getWebhookEventType(),
                            'priority' => $webhook->getJobPriority(),
                            'url' => $url,
                            'headers' => $custom_headers,
                            'payload' => json_encode($payload),
                        ]
                    ),
                    WebhooksIntegration::JOBS_QUEUE_CHANNEL
                );

                $this->logger->event(
                    'webhook_dispatched',
                    "Webhook for '{event_type}' event dispatched to '{url}'.",
                    [
                        'event_type' => $event->getWebhookEventType(),
                        'url' => $webhook->getUrl(),
                    ]
                );
            } catch (Exception $e) {
                $this->logger->error(
                    'Failed to dispatch webhook to {url}. Reason: {reason}',
                    [
                        'url' => $url,
                        'reason' => $e->getMessage(),
                        'custom_headers' => $custom_headers,
                        'exception' => $e,
                    ]
                );

                throw $e;
            }
        }
    }

    /**
     * @return Webhook[]|iterable
     */
    private function getEnabledWebhooks(): iterable
    {
        $enabled_webhooks = call_user_func($this->enabled_webhooks_resolver);

        if (empty($enabled_webhooks) || !is_iterable($enabled_webhooks)) {
            $enabled_webhooks = [];
        }

        return $enabled_webhooks;
    }
}
