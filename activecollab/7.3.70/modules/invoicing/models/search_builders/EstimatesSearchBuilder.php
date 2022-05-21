<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchBuilder\SearchBuilder;

final class EstimatesSearchBuilder extends SearchBuilder
{
    public function getName()
    {
        return 'Rebuild estimates search index';
    }

    protected function getRecordsToAdd()
    {
        return Estimates::find(
            [
                'order' => 'id',
            ]
        );
    }
}
