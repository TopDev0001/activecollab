<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger;

use ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger\Reference\WebhookLogReference;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger\Reference\WebhookLogReferenceInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\HealthProfile\WebhookHealthProfile;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\HealthProfile\WebhookHealthProfileInterface;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateTimeValueInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class WebhookLogger implements WebhookLoggerInterface
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function createReference(
        int $instance_id,
        int $webhook_id,
        string $url,
        string $json_payload
    ): WebhookLogReferenceInterface
    {
        return new WebhookLogReference(
            $this->connection->insert(
                'webhook_log',
                [
                    'instance_id' => $instance_id,
                    'webhook_id' => $webhook_id,
                    'url' => $url,
                    'payload' => $json_payload,
                    'status' => self::STATUS_PENDING,
                    'sent_on' => new DateTimeValue(),
                ]
            ),
            microtime(true)
        );
    }

    public function logResponse(
        WebhookLogReferenceInterface $reference,
        ResponseInterface $response
    ): void {
        $this->connection->update(
            'webhook_log',
            [
                'status' => $this->getStatusFromStatusCode($response->getStatusCode()),
                'request_time' => $reference->getExecutionTime(),
                'status_code' => $response->getStatusCode(),
                'response_phrase' => $response->getReasonPhrase(),
            ],
            [
                '`id` = ?', $reference->getReference()
            ]
        );
    }

    private function getStatusFromStatusCode(int $status_code): string
    {
        if ($status_code >= 200 && $status_code < 300) {
            return self::STATUS_SUCCESS;
        }

        return self::STATUS_FAILURE;
    }

    public function logException(
        WebhookLogReferenceInterface $reference,
        Throwable $exception
    ): void {
        $this->connection->update(
            'webhook_log',
            [
                'status' => self::STATUS_EXCEPTION,
                'request_time' => $reference->getExecutionTime(),
                'response_phrase' => $exception->getMessage()
            ],
            [
                '`id` = ?', $reference->getReference()
            ]
        );
    }

    public function countRecords(
        int $instance_id,
        int $webhook_id
    ): int
    {
        return $this->connection->count(
            'webhook_log',
            [
                '`instance_id` = ? AND `webhook_id` = ?',
                $instance_id,
                $webhook_id,
            ]
        );
    }

    public function latestLogsAreSuccesses(
        int $instance_id,
        int $webhook_id,
        int $logs_to_check = 10,
        DateTimeValue $since = null
    ): bool
    {
        $statuses = $this->getLatestStatuses($instance_id, $webhook_id, $since, $logs_to_check);

        if (empty($statuses)) {
            return false;
        }

        if (count($statuses) < $logs_to_check) {
            return false;
        }

        foreach ($statuses as $status) {
            if ($status !== WebhookLoggerInterface::STATUS_SUCCESS) {
                return false;
            }
        }

        return true;
    }

    public function latestLogsAreFailures(
        int $instance_id,
        int $webhook_id,
        int $logs_to_check = 10,
        DateTimeValue $since = null
    ): bool
    {
        $statuses = $this->getLatestStatuses($instance_id, $webhook_id, $since, $logs_to_check);

        if (empty($statuses)) {
            return false;
        }

        if (count($statuses) < $logs_to_check) {
            return false;
        }

        foreach ($statuses as $status) {
            if ($status === WebhookLoggerInterface::STATUS_SUCCESS) {
                return false;
            }
        }

        return true;
    }

    private function getLatestStatuses(
        int $instance_id,
        int $webhook_id,
        ?DateTimeValue $since,
        int $logs_to_check
    ): ?array
    {
        $conditions = $this->connection->prepare(
            '`instance_id` = ? AND `webhook_id` = ? AND `status` != ?',
            $instance_id,
            $webhook_id,
            WebhookLoggerInterface::STATUS_PENDING,
        );

        if ($since) {
            $conditions .= $this->connection->prepare(' AND `sent_on` > ?', $since);
        }

        return $this->connection->executeFirstColumn(
            sprintf(
                'SELECT `status` FROM `webhook_log` WHERE %s ORDER BY `sent_on` DESC, `id` DESC LIMIT 0, %d',
                $conditions,
                $logs_to_check
            )
        );
    }

    public function getHealthProfile(
        int $instance_id,
        int $webhook_id,
        DateTimeValueInterface $since = null
    ): WebhookHealthProfileInterface
    {
        $successes = 0;
        $failures = 0;
        $exceptions = 0;

        $conditions = $this->connection->prepare(
            '`instance_id` = ? AND `webhook_id` = ? AND `status` != ?',
            $instance_id,
            $webhook_id,
            self::STATUS_PENDING
        );

        if ($since) {
            $conditions .= $this->connection->prepare(' AND `sent_on` > ?', $since);
        }

        $rows = $this->connection->execute(
            sprintf(
                'SELECT COUNT(`id`) AS "row_count", `status`
                        FROM `webhook_log`
                        WHERE %s
                        GROUP BY `status`',
                $conditions
            )
        );

        if ($rows) {
            foreach ($rows as $row) {
                switch ($row['status']) {
                    case self::STATUS_SUCCESS:
                        $successes = (int) $row['row_count'];
                        break;
                    case self::STATUS_FAILURE:
                        $failures = (int) $row['row_count'];
                        break;
                    case self::STATUS_EXCEPTION:
                        $exceptions = (int) $row['row_count'];
                        break;
                }
            }
        }

        return new WebhookHealthProfile($successes, $failures, $exceptions);
    }
}
