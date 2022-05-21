<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Base expenses collection.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
abstract class ExpensesCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    public function getModelName(): string
    {
        return 'Expenses';
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
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    /**
     * @return string
     */
    protected function getTimestampHash()
    {
        if ($this->getCurrentPage() && $this->getItemsPerPage()) {
            $limit = ' LIMIT ' . (($this->getCurrentPage() - 1) * $this->getItemsPerPage()) . ', ' . $this->getItemsPerPage();
        } else {
            $limit = '';
        }

        return sha1(DB::executeFirstCell("SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM expenses WHERE " . $this->getQueryConditions() . ' ORDER BY ' . $this->getOrderBy() . $limit));
    }

    /**
     * Prepare query conditions.
     *
     * @return string
     * @throws ImpossibleCollectionError
     */
    abstract protected function getQueryConditions();

    /**
     * Return how expenses should be ordered.
     *
     * @return string
     */
    protected function getOrderBy()
    {
        return 'record_date DESC, id DESC';
    }

    /**
     * @return array
     */
    public function execute()
    {
        try {
            $expenses = $this->queryExpenses();
        } catch (ImpossibleCollectionError $e) {
            $expenses = null;
        }

        return [
            'expenses' => $expenses,
            'related' => $this->getRelatedFromExpenses($expenses),
        ];
    }

    /**
     * @return Expense[]|DbResult
     */
    protected function queryExpenses()
    {
        if ($this->getCurrentPage() && $this->getItemsPerPage()) {
            $offset = ($this->getCurrentPage() - 1) * $this->getItemsPerPage();
            $limit = $this->getItemsPerPage();
        } else {
            $offset = null;
            $limit = null;
        }

        return Expenses::find([
            'conditions' => $this->getQueryConditions(),
            'order' => $this->getOrderBy(),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Load related projects and tasks based on a list of expenses.
     *
     * @param  Expense[] $expenses
     * @return array
     */
    private function getRelatedFromExpenses($expenses)
    {
        $type_ids_map = ['Project' => [], 'Task' => []];

        /** @var Expense[] $expenses */
        if ($expenses) {
            foreach ($expenses as $expense) {
                if ($expense->getParentType() == 'Project' && !in_array($expense->getParentId(), $type_ids_map['Project'])) {
                    $type_ids_map['Project'][] = $expense->getParentId();
                } else {
                    if ($expense->getParentType() == 'Task' && !in_array($expense->getParentId(), $type_ids_map['Task'])) {
                        $type_ids_map['Task'][] = $expense->getParentId();
                    }
                }
            }
        }

        if (empty($type_ids_map['Task'])) {
            unset($type_ids_map['Task']);
        } else {
            if ($project_ids = DB::executeFirstColumn('SELECT DISTINCT project_id FROM tasks WHERE id IN (?)', $type_ids_map['Task'])) {
                foreach ($project_ids as $project_id) {
                    if (!in_array($project_id, $type_ids_map['Project'])) {
                        $type_ids_map['Project'][] = $project_id;
                    }
                }
            }
        }

        if (empty($type_ids_map['Project'])) {
            unset($type_ids_map['Project']);
        }

        return DataObjectPool::getByTypeIdsMap($type_ids_map);
    }

    /**
     * @return int
     */
    public function count()
    {
        try {
            return Expenses::count($this->getQueryConditions());
        } catch (ImpossibleCollectionError $e) {
            return 0;
        }
    }

    /**
     * Prepare pagination from bits.
     *
     * @param  int               $items_per_page
     * @throws InvalidParamError
     */
    protected function preparePaginationFromCollectionName(array &$bits, $items_per_page = 100)
    {
        $page = array_pop($bits);

        if (is_numeric($page)) {
            $page = (int) $page;
        } else {
            throw new InvalidParamError('bits', $bits, 'Page expected');
        }

        $separator = array_pop($bits);

        if ($separator === 'page') {
            $this->setPagination($page, $items_per_page);
        } else {
            throw new InvalidParamError('bits', $bits, '_page_ separator expected');
        }
    }

    /**
     * Prepare and return ID from name $bits array.
     *
     * @return int
     * @throws InvalidParamError
     */
    protected function prepareIdFromCollectionName(array &$bits)
    {
        $id = array_pop($bits);

        if (is_numeric($id)) {
            return (int) $id;
        } else {
            throw new InvalidParamError('bits', $bits, 'Expected ID as last bit');
        }
    }

    /**
     * Members and subcontractors can see only their expenses.
     */
    protected function filterExpensesByUserRole(User $user, Project $project, array &$conditions)
    {
        if (!($user instanceof Client || $user->isOwner() || $project->isLeader($user))) {
            $conditions[] = DB::prepare('(user_id = ?)', $user->getId()); // Just for memebers and subcontractors
        }
    }
}
