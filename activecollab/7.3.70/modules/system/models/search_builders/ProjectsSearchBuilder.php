<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchBuilder\SearchBuilder;

final class ProjectsSearchBuilder extends SearchBuilder
{
    public function getName()
    {
        return 'Rebuild projects search index';
    }

    protected function getRecordsToAdd()
    {
        return Projects::find(
            [
                'conditions' => ['`is_trashed` = ?', false],
                'order' => 'id',
            ]
        );
    }
}
