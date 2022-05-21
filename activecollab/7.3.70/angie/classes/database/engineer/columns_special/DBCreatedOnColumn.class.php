<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBCreatedOnColumn extends DBDateTimeColumn
{
    public function __construct()
    {
        parent::__construct('created_on');
    }

    public function addedToTable(): void
    {
        $this->table->addModelTrait(
            ICreatedOn::class,
            ICreatedOnImplementation::class
        );

        parent::addedToTable();
    }
}
