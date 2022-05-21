<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBUpdatedOnByColumn extends DBActionOnByColumn
{
    public function __construct(
        bool $key_on_date = false,
        bool $key_on_by = false
    )
    {
        parent::__construct('updated', $key_on_date, $key_on_by);
    }

    public function addedToTable(): void
    {
        $this->table->addModelTrait(
            [
                IUpdatedOn::class => IUpdatedOnImplementation::class,
                IUpdatedBy::class => IUpdatedByImplementation::class,
            ]
        );

        parent::addedToTable();
    }
}
