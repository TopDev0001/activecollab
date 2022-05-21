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
 * @package activeCollab.modules.system
 * @subpackage handlers
 */

/**
 * Handle on_search_filters event.
 *
 * @param array $filters
 */
function system_handle_on_search_filters(array &$filters)
{
    $filters['project_id'] = SearchItemInterface::FIELD_NUMERIC;
}
