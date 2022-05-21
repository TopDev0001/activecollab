<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils;

use BudgetThresholds;
use DateTimeValue;
use DB;
use DBQueryError;
use Exception;
use InvalidParamError;
use Project;
use Projects;
use Users;

class BudgetNotificationsManager implements BudgetNotificationsManagerInterface
{
    /**
     * Returns all projects that were updated in the last hour.
     * @throws DBQueryError
     * @throws InvalidParamError
     */
    public function getProjectsIds(): array
    {
        $query = 'SELECT DISTINCT p.id as project_id FROM projects p
            JOIN budget_thresholds bt
            ON p.id = bt.project_id
            WHERE p.updated_on > ?
            AND p.is_tracking_enabled > 0
            AND p.budget > 0
            AND p.is_trashed = 0
            AND p.is_sample = 0';

        $result = DB::executeFirstColumn($query, new DateTimeValue('- 2 HOURS'));

        $projects = [];

        if ($result) {
            $projects = $result;
        }

        return $projects;
    }

    public function findProjectsThatReachedThreshold(): array
    {
        $projects_to_notify = [];
        $updated_project_ids = $this->getProjectsIds();
        if (!empty($updated_project_ids)) {
            foreach ($updated_project_ids as $project_id) {
                /** @var Project $project */
                $project = Projects::findById($project_id);
                $spent_in_percents = $project->getCostSoFarInPercent(Users::findFirstOwner());
                $reached_threshold_to_notify = $this->getHighestReachedThreshold($project_id, $spent_in_percents);

                if ($reached_threshold_to_notify) {
                    $projects_to_notify[] = [
                        'project' => $project,
                        'threshold' => [
                            'threshold' => $reached_threshold_to_notify['threshold'],
                            'threshold_id' => $reached_threshold_to_notify['id'],
                            'type' => $reached_threshold_to_notify['type'],
                        ],
                    ];
                }
            }
        }

        return $projects_to_notify;
    }

    public function getHighestReachedThreshold(int $project_id, float $spent_in_percents): array
    {
        $highest_threshold = [];

        $query = 'SELECT id, type, threshold FROM budget_thresholds 
            WHERE project_id = ? 
            AND is_notification_sent = 0 
            AND threshold <= ? 
            ORDER BY threshold DESC 
            LIMIT 1';

        $result = DB::executeFirstRow($query, $project_id, $spent_in_percents);

        if ($result) {
            $highest_threshold = $result;
        }

        return $highest_threshold;
    }

    public function batchEditThresholds(array $thresholds, int $project_id): array
    {
        try {
            DB::beginWork('Begin: batch edit thresholds');
            $existing = DB::execute('SELECT id, threshold, is_notification_sent, notification_sent_on FROM budget_thresholds WHERE project_id = ? ORDER BY threshold', $project_id);

            $new = [];
            foreach (array_unique($thresholds) as $threshold) {
                $data = [
                    'project_id' => $project_id,
                    'type' => 'income',
                    'threshold' => $threshold,
                ];

                if ($existing) {
                    foreach ($existing->toArray() as $item) {
                        if (($threshold <= $item['threshold']) && $item['is_notification_sent']) {
                            $data['is_notification_sent'] = true;
                            $data['notification_sent_on'] = $item['notification_sent_on'];
                            break;
                        }
                    }
                }

                $new[] = $data;
            }

            if ($existing) {
                $existing_ids = array_column($existing->toArray(), 'id');
                BudgetThresholds::delete(DB::prepare('id IN (?)', $existing_ids));
            }

            $created = BudgetThresholds::createMany($new);

            DB::commit('Done: batch edit thresholds');

            return $created;
        } catch (Exception $e) {
            DB::rollback('Rollback: batch edit thresholds');
            throw $e;
        }
    }

    /**
     * @throws DBQueryError
     * @throws InvalidParamError
     */
    public function updateThresholds(Project $project): void
    {
        $spent_in_percents = $project->getCostSoFarInPercent(Users::findFirstOwner());
        $getThresholdsQuery = '
            SELECT threshold FROM budget_thresholds
            WHERE project_id = ? AND type = ? AND is_notification_sent = 1
            ORDER BY threshold ASC';

        $sendThresholds = DB::execute($getThresholdsQuery, $project->getId(), 'income');
        if ($sendThresholds) {
            foreach ($sendThresholds as $threshold) {
                if (intval($spent_in_percents) <= intval($threshold['threshold'])) {
                    $dropThresholdQuery = '
                        DELETE FROM budget_thresholds_notifications
                        WHERE project_id = ? AND threshold = ? AND type = ?';
                    $updateThresholdQuery = '
                        UPDATE budget_thresholds
                        SET is_notification_sent = 0, notification_sent_on = NULL, updated_on = ?
                        WHERE project_id = ? AND threshold = ? AND type = ?';
                    DB::execute($dropThresholdQuery, $project->getId(), $threshold['threshold'], 'income');
                    DB::execute($updateThresholdQuery, DateTimeValue::now(), $project->getId(), $threshold['threshold'], 'income');
                }
            }
        }
    }
}
