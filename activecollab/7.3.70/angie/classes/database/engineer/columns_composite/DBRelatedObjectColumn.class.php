<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBRelatedObjectColumn extends DBCompositeColumn
{
    private string $relation_name;
    private bool $add_key;

    public function __construct(
        string $relation_name,
        bool $add_key = true,
        bool $can_be_null = true
    )
    {
        $this->relation_name = $relation_name;
        $this->add_key = $add_key;

        $this->columns = [
            DBStringColumn::create(
                "{$relation_name}_type",
                DBStringColumn::MAX_LENGTH,
                $can_be_null ? null : ''
            ),
            DBIntegerColumn::create(
                "{$relation_name}_id",
                DBColumn::NORMAL,
                $can_be_null ? null : 0
            )->setUnsigned(true),
        ];
    }

    /**
     * Construct and return related object column.
     *
     * @return DBRelatedObjectColumn
     */
    public static function create(
        string $relation_name,
        bool $add_key = true,
        bool $can_be_null = true
    )
    {
        return new self($relation_name, $add_key, $can_be_null);
    }

    public function addedToTable(): void
    {
        if ($this->add_key) {
            $this->table->addIndex(
                new DBIndex(
                    $this->relation_name,
                    DBIndex::KEY,
                    [
                        "{$this->relation_name}_type",
                        "{$this->relation_name}_id",
                    ]
                )
            );
        }

        parent::addedToTable();
    }
}
