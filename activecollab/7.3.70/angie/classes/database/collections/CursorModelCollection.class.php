<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class CursorModelCollection extends ModelCollection implements CursorCollectionInterface
{
    /**
     * @param int|string|null $cursor
     */
    private $cursor = null;
    private int $limit = 100;

    public function getNextCursor()
    {
        if ($this->count() > $this->getLimit()) {
            $ids = $this->executeIds();

            return (int) end($ids);
        } else {
            return null;
        }
    }

    public function getConditions(): ?string
    {
        $conditions = '';

        if ($this->getCursor()) {
            $conditions .= DB::prepare('id < ?', $this->getCursor());
        }

        if ($parent_conditions = parent::getConditions()) {
            if ($conditions) {
                $conditions .= " AND {$parent_conditions}";
            } else {
                $conditions = $parent_conditions;
            }
        }

        return $conditions;
    }

    /**
     * @param int|string|null $cursor
     */
    public function setCursor($cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * @return int|string|null
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit >= 1 && $limit <= 1000
            ? $limit
            : 100;
    }

    protected function getSelectSql(bool $all_fields = true): string
    {
        $fields = $all_fields ? '*' : 'id';

        $table_name = $this->getTableName();
        $conditions = $this->getConditions() ? "WHERE {$this->getConditions()}" : '';

        $order_by = "ORDER BY {$this->getOrderBy()}";
        $limit = "LIMIT {$this->getLimit()}";

        if ($join_expression = $this->getJoinExpression()) {
            return "SELECT $table_name.$fields FROM $table_name $join_expression $conditions $order_by $limit";
        } else {
            return "SELECT $fields FROM $table_name $conditions $order_by $limit";
        }
    }

    protected function prepareTagFromBits($user_email, $hash)
    {
        return '"' . implode(',', [APPLICATION_VERSION, 'cursor_collection', $this->getModelName(), $this->getName(), $user_email, $hash]) . '"';
    }

    public function getOrderBy(): string
    {
        return 'id DESC';
    }
}
