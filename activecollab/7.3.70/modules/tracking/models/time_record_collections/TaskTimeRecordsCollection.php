<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Task time records collection.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
class TaskTimeRecordsCollection extends TimeRecordsCollection
{
    /**
     * @var int
     */
    private $task_id;

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

        $this->preparePaginationFromCollectionName($bits);
        $this->task_id = $this->prepareIdFromCollectionName($bits);
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
            $task = DataObjectPool::get('Task', $this->task_id);
            $project = $task instanceof Task ? DataObjectPool::get('Project', $task->getProjectId()) : null;

            if ($user instanceof User && $task instanceof Task && $project instanceof Project) {
                // ---------------------------------------------------
                //  If client report is disabled for this project or
                //  selected task is hidden from clients, we have
                //  nothing to look at here
                // ---------------------------------------------------

                if ($user instanceof Client && (!$project->getIsClientReportingEnabled() || $task->getIsHiddenFromClients())) {
                    throw new ImpossibleCollectionError();
                }

                $conditions = [DB::prepare('(is_trashed = ?)', false)]; // Not trashed

                $this->filterTimeRecordsByUserRole($user, $project, $conditions);

                $conditions[] = DB::prepare('(parent_type = ? AND parent_id = ?)', 'Task', $task->getId(), false);

                $this->query_conditions = implode(' AND ', $conditions);
            } else {
                throw new ImpossibleCollectionError();
            }
        }

        return $this->query_conditions;
    }
}
