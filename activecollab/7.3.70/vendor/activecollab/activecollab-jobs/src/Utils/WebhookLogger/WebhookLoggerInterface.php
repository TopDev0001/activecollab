<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger;

use ActiveCollab\ActiveCollabJobs\Jobs\Http\SendWebhook;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger\Reference\WebhookLogReferenceInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\HealthProfile\WebhookHealthProfileInterface;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateTimeValueInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

interface WebhookLoggerInterface
{
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';
    const STATUS_EXCEPTION = 'exception';

    public function createReference(
        int $instance_id,
        int $webhook_id,
        string $url,
        string $json_payload
    ): WebhookLogReferenceInterface;

    public function logResponse(
        WebhookLogReferenceInterface $reference,
        ResponseInterface  $response
    ): void;

    public function logException(
        WebhookLogReferenceInterface $reference,
        Throwable $exception
    ): void;

    public function countRecords(
        int $instance_id,
        int $webhook_id
    ): int;

    public function latestLogsAreSuccesses(
        int $instance_id,
        int $webhook_id,
        int $logs_to_check = 10,
        DateTimeValue $since = null
    ): bool;

    public function latestLogsAreFailures(
        int $instance_id,
        int $webhook_id,
        int $logs_to_check = 10,
        DateTimeValue $since = null
    ): bool;

    public function getHealthProfile(
        int $instance_id,
        int $webhook_id,
        DateTimeValueInterface $since = null
    ): WebhookHealthProfileInterface;
}
