<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\Tracking\Utils;

use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use Angie\Notifications\NotificationsInterface;
use BudgetThreshold;
use BudgetThresholds;
use BudgetThresholdsNotifications;
use DateTimeValue;
use DB;
use Exception;
use Project;
use Psr\Log\LoggerInterface;
use Users;

class BudgetNotificationsMaintenanceRunner implements BudgetNotificationsMaintenanceRunnerInterface
{
    private NotificationsInterface $notifications_service;
    private BudgetNotificationsManagerInterface $budget_notifications_manager;
    private AccountIdResolverInterface $account_id_resolver;
    private LoggerInterface $logger;

    public function __construct(
        NotificationsInterface $notifications_service,
        BudgetNotificationsManagerInterface $budget_notifications_manager,
        AccountIdResolverInterface $account_id_resolver,
        LoggerInterface $logger
    )
    {
        $this->notifications_service = $notifications_service;
        $this->budget_notifications_manager = $budget_notifications_manager;
        $this->account_id_resolver = $account_id_resolver;
        $this->logger = $logger;
    }

    public function run() {
        $this->logger->info(
            sprintf(
                'Running project budget notification maintenance for account #%d.',
                $this->account_id_resolver->getAccountId()
            )
        );
        $projects = $this->budget_notifications_manager->findProjectsThatReachedThreshold();
        foreach ($projects as $project) {
            $this->runProjectsBudgetNotifying($project['project'], $project['threshold']);
        }

        $this->logger->info(
            sprintf(
                'Finished project budget notification maintenance for account #%d',
                $this->account_id_resolver->getAccountId()
            )
        );
    }

    public function runProjectsBudgetNotifying($project, $threshold)
    {
        try {
            DB::beginWork('Running project budget maintenance @ '. __CLASS__);
            $recipients = $this->getRecipientsForMail($project);
            $this->sendNotification($project, $recipients, $threshold);
            DB::commit('Finished project budget notifying @ ' . __CLASS__);
        } catch (Exception $exception){
            DB::rollback('Rollback project budget notifying action @ ' . __CLASS__);
            $this->logger->error('Failed to maintain project budget notifications.', [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    public function sendNotification(Project $project, array $users, array $threshold)
    {
        $this->notifications_service
            ->notifyAbout('tracking/budget_threshold_reached', $project)
            ->setProjectName($project->getName())
            ->setProjectUrl($project->getViewUrl())
            ->setThreshold($threshold['threshold'])
            ->sendToUsers($users, true);

        $this->markAsSent($project->getId(), $threshold, $users);
    }

    public function getRecipientsForMail(Project $project): array
    {
        if ($project->getLeader() && $project->getLeader()->isActive()) {
            return [$project->getLeader()];
        } elseif ($project->getCreatedById() && $project->getCreatedBy()->isActive()) {
            return [$project->getCreatedBy()];
        }

        $ids = $project->getMemberIds();
        $recipients = [];

        foreach ($ids as $id) {
            $user = Users::findById($id);
            if ($user->isFinancialManager() && $user->isActive()) {
                array_push($recipients, $user);
            }
        }

        return $recipients;
    }

    private function markAsSent(int $project_id, array $threshold, array $users)
    {
        /** @var BudgetThreshold $budgetThreshold */
        $budgetThreshold = BudgetThresholds::findOneBy(['project_id' => $project_id, 'threshold' => $threshold['threshold'], 'type' => $threshold['type']]);
        $sentDateTime = DateTimeValue::now();
        BudgetThresholds::update($budgetThreshold, [
            'is_notification_sent' => true,
            'notification_sent_on' => $sentDateTime,
        ]);

        foreach ($users as $user) {
            BudgetThresholdsNotifications::create([
                'project_id' => $project_id,
                'type' => $threshold['type'],
                'threshold' => $threshold['threshold'],
                'user_id' => $user->getId(),
                'sent_at' => $sentDateTime->toMySQL(),
            ]);
        }

        $this->markLowerAsSent($project_id, $threshold, $users);
    }

    /**
     * Mark thresholds below the gauge which are not already sent as sent.
     */
    private function markLowerAsSent(int $project_id, array $threshold, array $users)
    {
        $query = '
            SELECT id, threshold
            FROM budget_thresholds
            WHERE project_id = ?
             AND type = ?
             AND threshold < ?
             AND is_notification_sent = 0';

        $lower_thresholds = DB::executeIdNameMap(
            $query,
            $project_id,
            $threshold['type'],
            $threshold['threshold'],
            function ($row) {
                return$row['threshold'];
            }
        );

        if ($lower_thresholds) {
            DB::execute('UPDATE budget_thresholds SET is_notification_sent = 1, notification_sent_on = ?, updated_on = ? WHERE id IN (?)', DateTimeValue::now()->toMySQL(), DateTimeValue::now()->toMySQL(), array_keys($lower_thresholds));
            foreach ($lower_thresholds as $lower_id => $lower_threshold_value) {
                foreach ($users as $user) {
                    BudgetThresholdsNotifications::create([
                        'project_id' => $project_id,
                        'type' => $threshold['type'],
                        'threshold' => $lower_threshold_value,
                        'user_id' => $user->getId(),
                        'sent_at' => DateTimeValue::now()->toMySQL(),
                    ]);
                }
            }
        }
    }
}
