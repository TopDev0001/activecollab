<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager;

use ActiveCollab\ActiveCollabJobs\Jobs\Http\SendWebhook;
use ActiveCollab\ActiveCollabJobs\Jobs\Instance\Webhooks\ChangeWebhookPriorityJob;
use ActiveCollab\ActiveCollabJobs\Jobs\Instance\Webhooks\DisableWebhookJob;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookDisabledAutomation;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookPriorityChangeAutomation;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookPriorityChangeAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\WebhookAutomationLoggerInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger\WebhookLoggerInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\WebhookDispatchResultInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\HealthProfile\WebhookHealthProfileInterface;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\JobsQueue\Jobs\JobInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;

class WebhooksHealthManager implements WebhooksHealthManagerInterface
{
    private JobsDispatcherInterface $jobs_dispatcher;
    private WebhookLoggerInterface $webhook_logger;
    private WebhookAutomationLoggerInterface $webhook_automation_logger;

    public function __construct(
        JobsDispatcherInterface $jobs_dispatcher,
        WebhookLoggerInterface $webhook_logger,
        WebhookAutomationLoggerInterface $webhook_automation_logger
    )
    {
        $this->jobs_dispatcher = $jobs_dispatcher;
        $this->webhook_logger = $webhook_logger;
        $this->webhook_automation_logger = $webhook_automation_logger;
    }

    public function notify(
        int $instance_id,
        int $webhook_id,
        int $job_priority,
        WebhookDispatchResultInterface $result
    ): void
    {
        if ($result->isSuccess()) {
            $this->handleSuccess(
                $instance_id,
                $webhook_id,
                $job_priority
            );

            return;
        }

        $this->handleFailure(
            $instance_id,
            $webhook_id,
            $job_priority
        );
    }

    private function handleSuccess(
        int $instance_id,
        int $webhook_id,
        int $job_priority
    ): void
    {
        if ($job_priority !== JobInterface::HAS_HIGHEST_PRIORITY
            && $this->webhook_logger->latestLogsAreSuccesses(
                $instance_id,
                $webhook_id,
                10,
                $this->getLatestChangeReference($instance_id, $webhook_id)
        )) {
            $this->increaseWebhookPriority($job_priority, $instance_id, $webhook_id);
        }
    }

    private function handleFailure(
        int $instance_id,
        int $webhook_id,
        int $job_priority
    ): void
    {
        if ($job_priority === JobInterface::NOT_A_PRIORITY) {
            $health_profile = $this->webhook_logger->getHealthProfile(
                $instance_id,
                $webhook_id,
                new DateTimeValue('-4 hours')
            );

            if ($this->shouldDisable(
                $instance_id,
                $webhook_id,
                $health_profile
            )) {
                $this->disableWebhook($instance_id, $webhook_id);
            }

            return;
        }

        if ($this->webhook_logger->latestLogsAreFailures(
            $instance_id,
            $webhook_id,
            10,
            $this->getLatestChangeReference($instance_id, $webhook_id)
        )) {
            $this->decreaseWebhookPriority($job_priority, $instance_id, $webhook_id);
        }
    }

    public function shouldDisable(
        int $instance_id,
        int $webhook_id,
        WebhookHealthProfileInterface $health_profile
    ): bool
    {
        if (!$this->profileIsLargeEnough($instance_id, $webhook_id, $health_profile)) {
            return false;
        }

        if ($this->failureRateIsLargeEnough($health_profile)) {
            return true;
        }

        return false;
    }

    private function profileIsLargeEnough(
        int $instance_id,
        int $webhook_id,
        WebhookHealthProfileInterface $health_profile
    ): bool
    {
        return $health_profile->countAll() >= 10 // At least ten records.
            && $health_profile->countAll() < $this->webhook_logger->countRecords($instance_id, $webhook_id); // And there are more records in the log.
    }

    private function failureRateIsLargeEnough(WebhookHealthProfileInterface $health_profile): bool
    {
        return $health_profile->getFailureRate() >= 50;
    }

    private function disableWebhook(int $instance_id, int $webhook_id): void
    {
        $this->webhook_automation_logger->recordDisabled(
            $instance_id,
            $webhook_id,
            new WebhookDisabledAutomation(new DateTimeValue())
        );

        $this->jobs_dispatcher->getQueue()->dequeueByType(
            SendWebhook::class,
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
            ]
        );

        if (!$this->jobs_dispatcher->exists(
            DisableWebhookJob::class,
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
            ]
        )) {
            $this->jobs_dispatcher->dispatch(
                new DisableWebhookJob(
                    [
                        'instance_id' => $instance_id,
                        'webhook_id' => $webhook_id,
                    ]
                )
            );
        }
    }

    private function increaseWebhookPriority(
        int $job_priority,
        int $instance_id,
        int $webhook_id
    ): void
    {
        $higher_priority = $this->getHigherPriority($job_priority);

        $priority_change = $this->getPriorityChange($job_priority, $higher_priority);

        if ($priority_change) {
            $this->webhook_automation_logger->recordPriorityChange(
                $instance_id,
                $webhook_id,
                new WebhookPriorityChangeAutomation(
                    $priority_change,
                    new DateTimeValue()
                )
            );
        }

        $this->jobs_dispatcher->getQueue()->changePriority(
            SendWebhook::class,
            $higher_priority,
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
            ]
        );

        $job_attributes = [
            'instance_id' => $instance_id,
            'webhook_id' => $webhook_id,
            'new_priority' => $this->intToVerbosePriority($higher_priority),
        ];

        if (!$this->jobs_dispatcher->exists(ChangeWebhookPriorityJob::class, $job_attributes)) {
            $this->jobs_dispatcher->dispatch(new ChangeWebhookPriorityJob($job_attributes));
        }
    }

    private function decreaseWebhookPriority(
        int $job_priority,
        int $instance_id,
        int $webhook_id
    ): void
    {
        $lower_priority = $this->getLowerPriority($job_priority);

        $priority_change = $this->getPriorityChange($job_priority, $lower_priority);

        if ($priority_change) {
            $this->webhook_automation_logger->recordPriorityChange(
                $instance_id,
                $webhook_id,
                new WebhookPriorityChangeAutomation(
                    $priority_change,
                    new DateTimeValue()
                )
            );
        }

        $this->jobs_dispatcher->getQueue()->changePriority(
            SendWebhook::class,
            $lower_priority,
            [
                'instance_id' => $instance_id,
                'webhook_id' => $webhook_id,
            ]
        );

        $this->jobs_dispatcher->dispatch(
            new ChangeWebhookPriorityJob(
                [
                    'instance_id' => $instance_id,
                    'webhook_id' => $webhook_id,
                    'new_priority' => $this->intToVerbosePriority($lower_priority),
                ]
            )
        );
    }

    private function getLatestChangeReference(
        int $instance_id,
        int $webhook_id
    ): ?DateTimeValue
    {
        $latest_change = $this->webhook_automation_logger->getLatestAutomation($instance_id, $webhook_id);

        if ($latest_change) {
            return $latest_change->getExecutedAt();
        }

        return null;
    }

    private function getPriorityChange(int $job_priority, int $new_priority): ?string
    {
        if ($job_priority === JobInterface::HAS_HIGHEST_PRIORITY && $new_priority === JobInterface::HAS_PRIORITY) {
            return WebhookPriorityChangeAutomationInterface::HIGH_TO_NORMAL;
        }

        if ($job_priority === JobInterface::NOT_A_PRIORITY && $new_priority === JobInterface::HAS_PRIORITY) {
            return WebhookPriorityChangeAutomationInterface::LOW_TO_NORMAL;
        }

        if ($job_priority === JobInterface::HAS_PRIORITY) {
            if ($new_priority === JobInterface::NOT_A_PRIORITY) {
                return WebhookPriorityChangeAutomationInterface::NORMAL_TO_LOW;
            } elseif ($new_priority === JobInterface::HAS_HIGHEST_PRIORITY) {
                return WebhookPriorityChangeAutomationInterface::NORMAL_TO_HIGH;
            }
        }

        return null;
    }

    private function intToVerbosePriority(int $job_priority): string
    {
        switch ($job_priority) {
            case JobInterface::HAS_HIGHEST_PRIORITY:
                return ChangeWebhookPriorityJob::PRIORITY_HIGH;
            case JobInterface::HAS_PRIORITY:
                return ChangeWebhookPriorityJob::PRIORITY_NORMAL;
        }

        return ChangeWebhookPriorityJob::PRIORITY_LOW;
    }

    private function getHigherPriority(int $current_job_priority): int
    {
        if ($current_job_priority === JobInterface::HAS_PRIORITY) {
            return JobInterface::HAS_HIGHEST_PRIORITY;
        }

        return JobInterface::HAS_PRIORITY;
    }

    private function getLowerPriority(int $current_job_priority): int
    {
        if ($current_job_priority === JobInterface::HAS_HIGHEST_PRIORITY) {
            return JobInterface::HAS_PRIORITY;
        }

        return JobInterface::NOT_A_PRIORITY;
    }
}
