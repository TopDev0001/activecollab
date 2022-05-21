<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class UserTimesheetReportCollection extends TimesheetReportCollection
{
    private $user_id;

    public function __construct($name)
    {
        parent::__construct($name);

        $this->user_id = $this->prepareIdFromCollectionName($this->bits);
    }

    protected function getQueryConditions()
    {
        if ($this->query_conditions === false) {
            $whos_asking = $this->getWhosAsking();

            if ($whos_asking instanceof Client) {
                throw new ImpossibleCollectionError();
            }

            $user = DataObjectPool::get(User::class, $this->user_id);

            if (!$user instanceof User || !$this->canSeeTimesheet($user, $whos_asking)) {
                throw new ImpossibleCollectionError();
            }

            $project_ids = DB::executeFirstColumn(
                'SELECT id FROM projects WHERE is_trashed = ? AND is_sample = ? AND is_tracking_enabled = ?',
                false,
                false,
                true
            );

            if (empty($project_ids)) {
                throw new ImpossibleCollectionError();
            }

            // User's untrashed records
            $conditions = [
                DB::prepare('(user_id = ? AND is_trashed = ?)', $user->getId(), false),
            ];

            if ($this->from_date && $this->to_date) {
                $conditions[] = DB::prepare('(record_date BETWEEN ? AND ?)', $this->from_date, $this->to_date);
            }

            $project_ids = DB::escape($project_ids);

            $task_time_tracking_enabled = ConfigOptions::getValue('task_time_tracking_enabled');

            if ($task_time_tracking_enabled) {
                $task_subquery = DB::prepare(
                    "SELECT id FROM tasks WHERE project_id IN ($project_ids) AND is_trashed = ?",
                    false
                );
                $conditions[] = DB::prepare(
                    "((parent_type = ? AND parent_id IN ($project_ids)) OR (parent_type = ? AND parent_id IN ($task_subquery)))",
                    Project::class,
                    Task::class
                );
            } else {
                $conditions[] = DB::prepare(
                    "parent_type = ? AND parent_id IN ($project_ids)",
                    Project::class
                );
            }

            $this->query_conditions = implode(' AND ', $conditions);
        }

        return $this->query_conditions;
    }

    private function canSeeTimesheet(User $user, User $whos_asking): bool
    {
        return $whos_asking->isOwner()
            || $user->getId() === $whos_asking->getId();
    }
}
