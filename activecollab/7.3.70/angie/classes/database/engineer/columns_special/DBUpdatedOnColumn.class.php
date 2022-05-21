<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBUpdatedOnColumn extends DBDateTimeColumn
{
    public function __construct()
    {
        parent::__construct('updated_on');
    }

    public function addedToTable(): void
    {
        $this->table->addModelTrait(IUpdatedOn::class, IUpdatedOnImplementation::class);

        parent::addedToTable();
    }
}
