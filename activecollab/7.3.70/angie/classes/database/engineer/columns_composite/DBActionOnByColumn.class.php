<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBActionOnByColumn extends DBCompositeColumn
{
    protected string $action;
    protected bool $key_on_date;
    protected bool $key_on_by;

    public function __construct(
        string $action,
        bool $key_on_date = false,
        bool $key_on_by = false
    )
    {
        $this->action = $action;
        $this->key_on_date = $key_on_date;
        $this->key_on_by = $key_on_by;

        $this->columns = [
            new DBDateTimeColumn($this->action . '_on'),
            DBIntegerColumn::create($this->action . '_by_id', DBColumn::NORMAL)->setUnsigned(true),
            DBStringColumn::create($this->action . '_by_name', 100),
            DBStringColumn::create($this->action . '_by_email', 150),
        ];
    }

    public function addedToTable(): void
    {
        if ($this->key_on_date) {
            $this->table->addIndex(new DBIndex($this->action . '_on', DBIndex::KEY, $this->action . '_on'));
        }

        if ($this->key_on_by) {
            $this->table->addIndex(new DBIndex($this->action . '_by_id', DBIndex::KEY, $this->action . '_by_id'));
        }

        parent::addedToTable();
    }
}
