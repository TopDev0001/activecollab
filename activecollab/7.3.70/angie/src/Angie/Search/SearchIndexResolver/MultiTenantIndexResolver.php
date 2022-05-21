<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchIndexResolver;

use InvalidArgumentException;

final class MultiTenantIndexResolver implements SearchIndexResolverInterface
{
    private $index_names;

    public function __construct(array $index_names)
    {
        foreach ($index_names as $key => $value) {
            if (!is_int($key)) {
                throw new InvalidArgumentException('Only integer keys are accepted.');
            }

            if (!is_string($value)) {
                throw new InvalidArgumentException('Index name needs to be a string value.');
            }
        }

        $this->index_names = $index_names;
    }

    public function getIndexNames()
    {
        return $this->index_names;
    }

    public function getIndexName($tenant_id)
    {
        if (!is_int($tenant_id)) {
            throw new InvalidArgumentException('Tenant ID needs to be a number.');
        }

        $mod = $tenant_id % 10;

        if (isset($this->index_names[$mod])) {
            return $this->index_names[$mod];
        } else {
            throw new \RuntimeException("Can't find index for tenant #{$tenant_id}.");
        }
    }
}
