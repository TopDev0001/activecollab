<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateFixMissingSearchSortPreference extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('search_sort_preference', 'score', false);
    }
}
