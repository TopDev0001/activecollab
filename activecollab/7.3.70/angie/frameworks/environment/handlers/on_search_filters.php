<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchItem\SearchItemInterface;

/**
 * on_search_filters event handler.
 *
 * @package angie.frameworks.environment
 * @subpackage handlers
 */

/**
 * Handle on_search_filters event.
 *
 * @param array $filters
 */
function environment_handle_on_search_filters(array &$filters)
{
    $filters['timestamps'] = SearchItemInterface::FIELD_DATETIME;
    $filters['created_by_id'] = SearchItemInterface::FIELD_NUMERIC;
    $filters['type'] = SearchItemInterface::FIELD_STRING;
}
