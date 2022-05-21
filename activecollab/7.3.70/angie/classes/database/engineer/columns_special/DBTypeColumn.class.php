<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBTypeColumn extends DBStringColumn
{
    private bool $add_key;

    public function __construct(
        string $default_type = 'ApplicationObject',
        int $length = 191,
        bool $add_key = true
    )
    {
        parent::__construct('type', $length, $default_type);

        $this->add_key = $add_key;
    }

    public function addedToTable(): void
    {
        if ($this->add_key) {
            $this->table->addIndex(new DBIndex('type', DBIndex::KEY, 'type'));
        }

        parent::addedToTable();
    }
}
