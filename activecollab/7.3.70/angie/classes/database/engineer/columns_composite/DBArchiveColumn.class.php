<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBArchiveColumn extends DBCompositeColumn
{
    private bool $cascade;
    private bool $record_archive_timestamp;

    public function __construct(
        bool $cascade = false,
        bool $record_archive_timestamp = false
    )
    {
        $this->cascade = $cascade;
        $this->record_archive_timestamp = $record_archive_timestamp;

        $this->columns = [
            new DBBoolColumn('is_archived'),
        ];

        if ($this->cascade) {
            $this->columns[] = new DBBoolColumn('original_is_archived');
        }

        if ($this->record_archive_timestamp) {
            $this->columns[] = new DBDateTimeColumn('archived_on');
        }
    }

    public function addedToTable(): void
    {
        if ($this->record_archive_timestamp) {
            $this->table->addIndex(new DBIndex('archived_on'));
        }

        parent::addedToTable();
    }
}
