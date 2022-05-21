<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher;

use ActiveCollab\ActiveCollabJobs\Utils\HttpRequestDispatcher\HttpRequestDispatcherInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger\WebhookLoggerInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\ExceptionWebhookDispatchResult;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\FailureWebhookDispatchResult;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\SuccessWebhookDispatchResult;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\WebhookDispatchResultInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;

class WebhooksDispatcher implements WebhooksDispatcherInterface
{
    private HttpRequestDispatcherInterface $http_request_dispatcher;
    private WebhookLoggerInterface $webhook_logger;

    public function __construct(
        HttpRequestDispatcherInterface $http_request_dispatcher,
        WebhookLoggerInterface $webhook_logger
    ) {
        $this->http_request_dispatcher = $http_request_dispatcher;
        $this->webhook_logger = $webhook_logger;
    }

    public function dispatch(
        int $instance_id,
        int $webhook_id,
        string $url,
        array $headers,
        string $json_payload,
        int $timeout = 600,
        bool $verify_peer = false
    ): WebhookDispatchResultInterface
    {
        if (!array_key_exists('User-Agent', $headers)) {
            $headers['User-Agent'] = 'Active Collab';
        }

        if ($webhook_id) {
            $log_reference = $this->webhook_logger->createReference(
                $instance_id,
                $webhook_id,
                $url,
                $json_payload
            );
        }

        try {
            $response = $this->http_request_dispatcher->postJson(
                $url,
                $headers,
                $json_payload,
                $timeout,
                $verify_peer
            );

            if ($webhook_id) {
                $this->webhook_logger->logResponse($log_reference, $response);
            }

            return $this->responseToResult($response);
        } catch (Exception $e) {
            if ($webhook_id) {
                $this->webhook_logger->logException($log_reference, $e);
            }

            return new ExceptionWebhookDispatchResult($e);
        }
    }

    private function responseToResult(ResponseInterface $response): WebhookDispatchResultInterface
    {
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return new SuccessWebhookDispatchResult();
        }

        return new FailureWebhookDispatchResult();
    }
}
