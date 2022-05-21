<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger;

use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookDisabledAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookPriorityChangeAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\AutomationFactory\WebhookAutomationFactoryInterface;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\JobsQueue\Jobs\JobInterface;
use Psr\Log\LoggerInterface;

class WebhookAutomationLogger implements WebhookAutomationLoggerInterface
{
    private ConnectionInterface $connection;
    private WebhookAutomationFactoryInterface $webhook_automation_factory;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        WebhookAutomationFactoryInterface $webhook_automation_factory,
        LoggerInterface $logger
    )
    {
        $this->connection = $connection;
        $this->webhook_automation_factory = $webhook_automation_factory;
        $this->logger = $logger;
    }

    public function getLatestAutomation(
        int $instance_id,
        int $webhook_id
    ): ?WebhookAutomationInterface
    {
        $latest_automation = $this->connection->executeFirstRow(
            'SELECT `automation`, `executed_at`
                    FROM `webhook_automation_log`
                    WHERE `instance_id` = ? AND `webhook_id` = ?
                    ORDER BY `executed_at` DESC
                    LIMIT 0, 1',
            $instance_id,
            $webhook_id
        );

        if ($latest_automation) {
            return $this->webhook_automation_factory->createAutomation(
                $latest_automation['automation'],
                $latest_automation['executed_at'],
            );
        }

        return null;
    }

    public function recordPriorityChange(
        int $instance_id,
        int $webhook_id,
        WebhookPriorityChangeAutomationInterface $priority_change_automation
    ): void
    {
        $this->logger->notice(
            'Priority change {priority_change} for webhook #{webhook_id}.',
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
                'priority_change' => $priority_change_automation->getPriorityChange(),
            ]
        );

        $this->connection->insert(
            'webhook_automation_log',
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
                'automation' => $priority_change_automation->getPriorityChange(),
                'executed_at' => $priority_change_automation->getExecutedAt(),
            ]
        );
    }

    public function recordDisabled(
        int $instance_id,
        int $webhook_id,
        WebhookDisabledAutomationInterface $disabled_automation
    ): void
    {
        $this->logger->notice(
            'Webhook #{webhook_id} disabled.',
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
            ]
        );

        $this->connection->insert(
            'webhook_automation_log',
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
                'automation' => WebhookDisabledAutomationInterface::DISABLED,
                'executed_at' => $disabled_automation->getExecutedAt(),
            ]
        );
    }

    public function jobPrioritiesToPriorityChange(
        int $from_priority,
        int $to_priority
    ): ?string
    {
        if ($from_priority === JobInterface::HAS_HIGHEST_PRIORITY && $to_priority === JobInterface::HAS_PRIORITY) {
            return WebhookPriorityChangeAutomationInterface::HIGH_TO_NORMAL;
        } elseif ($from_priority === JobInterface::HAS_PRIORITY && $to_priority === JobInterface::NOT_A_PRIORITY) {
            return WebhookPriorityChangeAutomationInterface::NORMAL_TO_LOW;
        } elseif ($from_priority === JobInterface::NOT_A_PRIORITY && $to_priority === JobInterface::HAS_PRIORITY) {
            return WebhookPriorityChangeAutomationInterface::LOW_TO_NORMAL;
        } elseif ($from_priority === JobInterface::HAS_PRIORITY && $to_priority === JobInterface::HAS_HIGHEST_PRIORITY) {
            return WebhookPriorityChangeAutomationInterface::NORMAL_TO_HIGH;
        }

        return null;
    }
}
