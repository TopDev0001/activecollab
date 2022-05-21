<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchQueryResolver;

use InvalidArgumentException;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;

final class DeleteDocumentsQueryResolver implements SearchQueryResolverInterface
{
    /**
     * @var int
     */
    private $tenant_id;

    public function __construct($tenant_id)
    {
        if (!is_int($tenant_id) || empty($tenant_id)) {
            throw new InvalidArgumentException('Valid tenant id is required.');
        }

        $this->tenant_id = $tenant_id;
    }

    public function getQuery()
    {
        $query = new BoolQuery();

        // add tenant id must query
        $query->add(
            new TermQuery(
                'tenant_id',
                $this->tenant_id
            )
        );

        return [
            'query' => $query->toArray(),
        ];
    }
}
