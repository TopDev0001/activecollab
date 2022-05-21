<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class RelativeCursorModelCollection extends CursorModelCollection implements RelativeCursorCollectionInterface
{
    private ?string $cursor_field = null;
    private ?int $last_id = null;

    public function getConditions(): ?string
    {
        $conditions = '';

        if ($this->getCursor() && $this->getLastId()) {
            $conditions .= DB::prepare(
                sprintf(
                    '(%s < ? OR (%s = ? AND id < ?))',
                    $this->getCursorField(),
                    $this->getCursorField()
                ),
                $this->getCursor(),
                $this->getCursor(),
                $this->getLastId()
            );
        }

        if ($parent_conditions = $this->conditions) {
            if ($conditions) {
                $conditions .= " AND {$parent_conditions}";
            } else {
                $conditions = $parent_conditions;
            }
        }

        return $conditions;
    }

    public function getNextCursor()
    {
        if ($this->count() > $this->getLimit()) {
            $ids = $this->executeIds();

            $this->setLastId((int) end($ids)); // set new 'last_id' when calculate next cursor

            return DB::executeFirstCell(
                sprintf(
                    "SELECT %s FROM {$this->getTableName()} WHERE id = ?",
                    $this->getCursorField()
                ),
                $this->getLastId()
            );
        } else {
            $this->setLastId(null); // reset 'last_id' when there is no cursor value

            return null;
        }
    }

    public function getLastId(): ?int
    {
        return $this->last_id;
    }

    public function setLastId(?int $last_id): void
    {
        $this->last_id = $last_id;
    }

    public function getCursorField(): string
    {
        return $this->cursor_field;
    }

    public function setCursorField(string $cursor_field): void
    {
        if (!$this->modelFieldExist($cursor_field)) {
            throw new InvalidParamError(
                'cursor_field',
                $cursor_field,
                '$cursor_field must be valid model field'
            );
        }

        if ($cursor_field === 'id') {
            throw new InvalidParamError(
                'cursor_field',
                $cursor_field,
                "$cursor_field must be different from 'id' value. Or use 'CursorModelCollection' class instead"
            );
        }

        $this->cursor_field = $cursor_field;
    }

    public function getOrderBy(): string
    {
        return sprintf(
            '%s DESC, id DESC',
            $this->getCursorField()
        );
    }
}
