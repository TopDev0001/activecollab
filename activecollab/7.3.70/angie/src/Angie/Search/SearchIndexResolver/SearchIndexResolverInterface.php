<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchIndexResolver;

interface SearchIndexResolverInterface
{
    public function getIndexNames();

    public function getIndexName($tenant_id);
}
