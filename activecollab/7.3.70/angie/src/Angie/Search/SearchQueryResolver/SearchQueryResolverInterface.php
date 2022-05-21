<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchQueryResolver;

interface SearchQueryResolverInterface
{
    /**
     * Return prepared search query.
     *
     * @return array
     */
    public function getQuery();
}
