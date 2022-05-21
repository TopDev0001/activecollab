<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchBuilder\SearchBuilder;

final class InvoicesSearchBuilder extends SearchBuilder
{
    public function getName()
    {
        return 'Rebuild invoices search index';
    }

    protected function getRecordsToAdd()
    {
        return Invoices::find(
            [
                'order' => 'id',
            ]
        );
    }
}
