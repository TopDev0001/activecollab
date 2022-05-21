<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class UserTimeRecordsCollection extends TimeRecordsCollection
{
    /**
     * @var DateValue
     */
    private $from_date;

    /**
     * @var DateValue
     */
    private $to_date;

    /**
     * @var int
     */
    private $user_id;

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

        if (str_starts_with($name, 'filtered_time_records_by_user')) {
            [
                $this->from_date,
                $this->to_date,
            ] = $this->prepareFromToFromCollectionName($bits);
        } else {
            $this->preparePaginationFromCollectionName($bits);
        }

        $this->user_id = $this->prepareIdFromCollectionName($bits);
    }

    /**
     * Prepare query conditions.
     *
     * @return string
     */
    protected function getQueryConditions()
    {
        if ($this->query_conditions === false) {
            $whos_asking = $this->getWhosAsking();

            if ($whos_asking instanceof Client) {
                throw new ImpossibleCollectionError();
            }

            $user = DataObjectPool::get(User::class, $this->user_id);

            if (!$user instanceof User) {
                throw new ImpossibleCollectionError();
            }

            if (!$this->canSeeUserTimeRecords($user, $whos_asking)) {
                throw new ImpossibleCollectionError();
            }

            $project_ids = Projects::findIdsByUser($user, true, ['is_trashed = ?', false]);

            if (empty($project_ids)) {
                throw new ImpossibleCollectionError();
            }

            $conditions = [
                DB::prepare('(user_id = ? AND is_trashed = ?)', $user->getId(), false),
            ];

            if ($this->from_date && $this->to_date) {
                $conditions[] = DB::prepare('(record_date BETWEEN ? AND ?)', $this->from_date, $this->to_date, false);
            }

            $project_ids = DB::escape($project_ids);
            $task_subquery = DB::prepare("SELECT id FROM tasks WHERE project_id IN ($project_ids) AND is_trashed = ?", false);

            $conditions[] = DB::prepare("((parent_type = 'Project' AND parent_id IN ($project_ids)) OR (parent_type = 'Task' AND parent_id IN ($task_subquery)))");

            $this->query_conditions = implode(' AND ', $conditions);
        }

        return $this->query_conditions;
    }

    protected function getOrderBy(): string
    {
        return $this->from_date && $this->to_date ? 'id' : 'record_date DESC, id DESC';
    }

    private function canSeeUserTimeRecords(User $user, User $whos_asking): bool
    {
        return $whos_asking->isOwner()
            || $user->getId() === $whos_asking->getId();
    }
}
