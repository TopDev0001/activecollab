<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class ProjectFinancialStatsCollection extends CompositeCollection
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $tag;

    /**
     * Run the query and return DB result.
     *
     * @return DbResult|DataObject[]
     */
    public function execute()
    {
        $costs = $this->project->calculateCosts();
        $incomes = $this->project->calculateIncomes();
        $profit = $incomes - $costs;
        $time_records_without_internal_rate = $this->project->checkIfThereAreRecordsWithoutInternalRate();

        return [
            'incomes' => $incomes,
            'costs' => $costs,
            'profit' => $profit,
            'records_without_internal_rate' => $time_records_without_internal_rate,
        ];
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        return 0;
    }

    public function getModelName(): string
    {
        return Projects::class;
    }

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if (!$this->tag || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits(
                $user->getEmail(),
                sha1(
                    $this->project->getUpdatedOn()->toMySQL() . '-' .
                    $this->getMaxTimeRecordsUpdateOn()
                )
            );
        }

        return $this->tag;
    }

    private function getMaxTimeRecordsUpdateOn()
    {
        $query = "
SELECT MAX(updated_on) 
FROM time_records WHERE (parent_type = 'Project' AND parent_id = ?) OR 
(parent_type = 'Task' AND parent_id IN (SELECT id FROM tasks WHERE project_id = ?))";

        return DB::executeFirstCell($query, $this->project->getId(), $this->project->getId());
    }

    public function &setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }
}
