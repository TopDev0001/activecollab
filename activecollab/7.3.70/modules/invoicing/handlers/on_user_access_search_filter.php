<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;

/**
 * on_user_search_filter event handler.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * @param User      $user
 * @param BoolQuery $query
 */
function invoicing_handle_on_user_access_search_filter(User $user, BoolQuery &$query)
{
    if ($user->isFinancialManager()) {
        // add as OR option to passed boolean query
        // will be true if type field is equal to invoce
        $query->add(new TermQuery('type', Invoice::class), BoolQuery::SHOULD);

        // add as OR option to passed boolean query
        // will be true if type field is equal to estimate
        $query->add(new TermQuery('type', Estimate::class), BoolQuery::SHOULD);
    }
}
