<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchBuilder\SearchBuilder;

final class UsersSearchBuilder extends SearchBuilder
{
    public function getName()
    {
        return 'Rebuild users search index';
    }

    protected function getRecordsToAdd()
    {
        return Users::find(
            [
                'conditions' => ['`is_trashed` = ?', false],
                'order' => 'id',
            ]
        );
    }
}
