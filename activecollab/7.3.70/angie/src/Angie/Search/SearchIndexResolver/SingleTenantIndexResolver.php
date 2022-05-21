<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchIndexResolver;

use InvalidArgumentException;

final class SingleTenantIndexResolver implements SearchIndexResolverInterface
{
    private $index_name;

    public function __construct($index_name)
    {
        if (!is_string($index_name) || empty($index_name)) {
            throw new InvalidArgumentException('Valid index name is required.');
        }

        $this->index_name = $index_name;
    }

    public function getIndexNames()
    {
        return [$this->index_name];
    }

    public function getIndexName($tenant_id)
    {
        return $this->index_name;
    }
}
