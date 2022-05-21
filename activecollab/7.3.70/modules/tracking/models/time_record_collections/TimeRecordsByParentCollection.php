<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class TimeRecordsByParentCollection extends TimeRecordsCollection
{
    private string $parent_type;
    private int $parent_id;
    private ?DateValue $from = null;
    private ?DateValue $to = null;

    private $query_conditions = false;

    protected bool $include_releated = false;

    public function __construct($name)
    {
        parent::__construct($name);

        $bits = explode('_', $name);
        $from_to_string = array_pop($bits);

        if (strpos($from_to_string, ':') === false) {
            throw new InvalidParamError('bits', $bits, 'Expected from:to bit');
        }

        [$from, $to] = explode(':', $from_to_string);

        $this->from = $from !== 'not-set' ? DateValue::makeFromString($from) : null;
        $this->to = $to !== 'not-set' ? DateValue::makeFromString($to) : null;

        $this->preparePaginationFromCollectionName($bits);

        $parent = array_pop($bits);

        if (strpos($parent, ':') === false) {
            throw new InvalidParamError('bits', $bits, 'Expected parent_type:parent_id bit');
        }

        [$parent_type, $parent_id] = explode(':', $parent);

        $this->parent_type = $parent_type;
        $this->parent_id = (int) $parent_id;
    }

    protected function getQueryConditions()
    {
        if ($this->query_conditions === false) {
            $user = $this->getWhosAsking();

            $conditions = [DB::prepare('(is_trashed = ?)', false)];

            if ($this->from && $this->to) {
                $conditions[] = DB::prepare('(record_date BETWEEN ? AND ?)', $this->from, $this->to);
            } elseif ($this->from && !$this->to) {
                $conditions[] = DB::prepare('(record_date >= ?)', $this->from);
            } elseif (!$this->from && $this->to) {
                $conditions[] = DB::prepare('(record_date <= ?)', $this->to);
            }

            $this->filterTimeRecordsByUserRole($user, $this->getProject(), $conditions);

            $conditions[] = DB::prepare('parent_type = ? AND parent_id = ?', $this->parent_type, $this->parent_id);

            $this->query_conditions = implode(' AND ', $conditions);
        }

        return $this->query_conditions;
    }

    private function getProject(): Project
    {
        /** @var Project|Task $parent */
        $parent = DataObjectPool::get($this->parent_type, $this->parent_id);

        return $parent instanceof Task ? $parent->getProject() : $parent;
    }
}
