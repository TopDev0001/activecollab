<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchEngineInterface;
use Psr\Log\LoggerInterface;

/**
 * @param SearchEngineInterface $search_engine
 * @param LoggerInterface       $logger
 * @param array                 $builders
 */
function invoicing_handle_on_search_rebuild_index(
    SearchEngineInterface $search_engine,
    LoggerInterface $logger,
    array &$builders
)
{
    $builders[] = new InvoicesSearchBuilder($search_engine, $logger);
    $builders[] = new EstimatesSearchBuilder($search_engine, $logger);
}
