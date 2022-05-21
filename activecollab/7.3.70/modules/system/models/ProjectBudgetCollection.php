<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project budget collection.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ProjectBudgetCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * @var array
     */
    private $cost_by_job_type = false;

    /**
     * @var array
     */
    private $expenses = false;

    /**
     * @var string
     */
    private $query_conditions = false;

    /**
     * @var Project
     */
    private $project;

    public function getModelName(): string
    {
        return 'Projects';
    }

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), sha1(
                $this->project->getUpdatedOn()->toMySQL() . '-' .
                DB::executeFirstCell("SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM time_records WHERE " . $this->getQueryConditions() . ' ORDER BY id') . '-' .
                DB::executeFirstCell("SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM expenses WHERE " . $this->getQueryConditions() . ' ORDER BY id')
            ));
        }

        return $this->tag;
    }

    /**
     * @return string
     */
    private function getQueryConditions()
    {
        if ($this->query_conditions === false) {
            $this->query_conditions = DB::prepare("((parent_type = 'Project' AND parent_id = ?) OR (parent_type = 'Task' AND parent_id IN (SELECT id FROM tasks WHERE project_id = ? AND is_trashed = ?))) AND is_trashed = ?", $this->project->getId(), $this->project->getId(), false, false);
        }

        return $this->query_conditions;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $billable_cost_so_far = 0;
        $non_billable_cost_so_far = 0;

        if ($cost_by_type = $this->calculateCostByJobType()) {
            foreach (array_keys($cost_by_type) as $k) {
                $cost_by_type[$k]['value'] = $cost_by_type[$k]['hours_decimal'] * $cost_by_type[$k]['rate'];
                $cost_by_type[$k]['non_billable_value'] = $cost_by_type[$k]['non_billable_hours_decimal'] * $cost_by_type[$k]['rate'];

                $cost_by_type[$k]['hours'] = minutes_to_time($cost_by_type[$k]['hours']);
                $cost_by_type[$k]['non_billable_hours'] = minutes_to_time($cost_by_type[$k]['non_billable_hours']);

                $billable_cost_so_far += $cost_by_type[$k]['value'];
                $non_billable_cost_so_far += $cost_by_type[$k]['non_billable_value'];
            }
        }

        [$billable_expenses, $non_billable_expenses] = $this->calculateExpenses();

        if ($billable_expenses) {
            $billable_cost_so_far += $billable_expenses;
        }

        if ($non_billable_expenses) {
            $non_billable_cost_so_far += $non_billable_expenses;
        }

        return [
            'budget' => $this->project->getBudget(),
            'cost_so_far' => $billable_cost_so_far + $non_billable_cost_so_far,
            'billable_cost_so_far' => $billable_cost_so_far,
            'non_billable_cost_so_far' => $non_billable_cost_so_far,
            'cost_by_job_type' => $cost_by_type,
            'expenses' => $billable_expenses,
            'non_billable_expenses' => $non_billable_expenses,
        ];
    }

    /**
     * @return array
     */
    private function calculateCostByJobType()
    {
        if ($this->cost_by_job_type === false) {
            $this->cost_by_job_type = [];

            $job_type_rates = JobTypes::getIdRateMapFor($this->project);

            foreach ($job_type_rates as $job_type_id => $job_type_rate) {
                $this->cost_by_job_type[$job_type_id] = [
                    'id' => $job_type_id,
                    'rate' => $job_type_rate,
                    'hours' => 0,
                    'hours_decimal' => 0,
                    'value' => 0,
                    'non_billable_hours' => 0,
                    'non_billable_hours_decimal' => 0,
                    'non_billable_value' => 0,
                ];
            }

            if ($rows = DB::execute("SELECT value AS 'hours', job_type_id, billable_status FROM time_records WHERE " . $this->getQueryConditions())) {
                $rows->setCasting(['hours' => DBResult::CAST_FLOAT, 'billable_status' => DBResult::CAST_INT]);

                foreach ($rows as $row) {
                    $job_type_id = (int) $row['job_type_id'];

                    if (isset($job_type_rates[$job_type_id])) {
                        if ($row['billable_status'] > TimeRecord::NOT_BILLABLE) {
                            $this->cost_by_job_type[$job_type_id]['hours'] += time_to_minutes($row['hours']);
                            $this->cost_by_job_type[$job_type_id]['hours_decimal'] += $row['hours'];
                        } else {
                            $this->cost_by_job_type[$job_type_id]['non_billable_hours'] += time_to_minutes($row['hours']);
                            $this->cost_by_job_type[$job_type_id]['non_billable_hours_decimal'] += $row['hours'];
                        }
                    }
                }
            }

            usort($this->cost_by_job_type, function ($a, $b) {
                return strcmp($a['id'], $b['id']);
            });
        }

        return $this->cost_by_job_type;
    }

    /**
     * @return array
     */
    private function calculateExpenses()
    {
        if ($this->expenses === false) {
            $this->expenses = [0, 0];

            if ($rows = DB::execute("SELECT SUM(value) AS 'value', billable_status FROM expenses WHERE " . $this->getQueryConditions() . ' GROUP BY billable_status', false, false)) {
                $rows->setCasting(['value' => DBResult::CAST_FLOAT, 'billable_status' => DBResult::CAST_INT]);

                foreach ($rows as $row) {
                    if ($row['billable_status'] == Expense::NOT_BILLABLE) {
                        $this->expenses[1] += $row['value'];
                    } else {
                        $this->expenses[0] += $row['value'];
                    }
                }
            }
        }

        return $this->expenses;
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        return 1;
    }

    /**
     * @return $this
     */
    public function &setProject(Project $project)
    {
        $this->project = $project;

        return $this;
    }
}
