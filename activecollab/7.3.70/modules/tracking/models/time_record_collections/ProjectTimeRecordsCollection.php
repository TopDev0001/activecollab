<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project time records collection.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
class ProjectTimeRecordsCollection extends TimeRecordsCollection
{
    /**
     * @var DateValue
     */
    private $from_date;
    private $to_date;

    /**
     * @var int
     */
    private $project_id;
    /**
     * @var string
     */
    private $query_conditions = false;

    /**
     * Construct the collection.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $bits = explode('_', $name);

        if (str_starts_with($name, 'filtered_time_records_in_project')) {
            [$this->from_date, $this->to_date] = $this->prepareFromToFromCollectionName($bits);
        } else {
            $this->preparePaginationFromCollectionName($bits);
        }

        $this->project_id = $this->prepareIdFromCollectionName($bits);
    }

    /**
     * Prepare query conditions.
     *
     * @return string
     * @throws ImpossibleCollectionError
     */
    protected function getQueryConditions()
    {
        if ($this->query_conditions === false) {
            $user = $this->getWhosAsking();
            $project = DataObjectPool::get('Project', $this->project_id);

            if ($user instanceof User && $project instanceof Project) {
                // ---------------------------------------------------
                //  If client report is disabled for this project, we
                //  have nothing to look at here
                // ---------------------------------------------------

                if ($user instanceof Client && !$project->getIsClientReportingEnabled()) {
                    throw new ImpossibleCollectionError();
                }

                $conditions = [DB::prepare('(is_trashed = ?)', false)]; // Not trashed

                if ($this->from_date && $this->to_date) {
                    $conditions[] = DB::prepare('(record_date BETWEEN ? AND ?)', $this->from_date, $this->to_date, false);
                }

                $this->filterTimeRecordsByUserRole($user, $project, $conditions);

                $task_time_tracking_enabled = ConfigOptions::getValue('task_time_tracking_enabled');

                if ($task_time_tracking_enabled) {
                    $conditions[] = DB::prepare("((parent_type = 'Project' AND parent_id = ?) OR (parent_type = 'Task' AND parent_id IN (" . $this->getTasksSubquery($user, $project) . ')))', $project->getId());
                } else {
                    $conditions[] = DB::prepare("parent_type = 'Project' AND parent_id = ?", $project->getId());
                }

                $this->query_conditions = implode(' AND ', $conditions);
            } else {
                throw new ImpossibleCollectionError();
            }
        }

        return $this->query_conditions;
    }

    /**
     * @return string
     */
    private function getTasksSubquery(User $user, Project $project)
    {
        if ($user instanceof Client) {
            return DB::prepare('SELECT id FROM tasks WHERE project_id = ? AND is_hidden_from_clients = ? AND is_trashed = ?', $project->getId(), false, false);
        } else {
            return DB::prepare('SELECT id FROM tasks WHERE project_id = ? AND is_trashed = ?', $project->getId(), false);
        }
    }

    /**
     * Return how time records should be ordered.
     *
     * @return string
     */
    protected function getOrderBy()
    {
        return $this->from_date && $this->to_date ? 'id' : 'record_date DESC, id DESC';
    }
}
