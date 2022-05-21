<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBTrashColumn extends DBCompositeColumn
{
    public function __construct(bool $cascade = false)
    {
        $this->columns = [
            new DBBoolColumn('is_trashed'),
        ];

        if ($cascade) {
            $this->columns[] = new DBBoolColumn('original_is_trashed');
        }

        $this->columns[] = new DBDateTimeColumn('trashed_on');
        $this->columns[] = DBFkColumn::create('trashed_by_id');
    }

    public function addedToTable(): void
    {
        $this->table->addIndex(new DBIndex('trashed_on'));
        $this->table->addIndex(new DBIndex('trashed_by_id'));

        parent::addedToTable();
    }
}
