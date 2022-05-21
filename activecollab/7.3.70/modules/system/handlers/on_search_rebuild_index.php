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
function system_handle_on_search_rebuild_index(
    SearchEngineInterface $search_engine,
    LoggerInterface $logger,
    array &$builders
)
{
    $builders[] = new UsersSearchBuilder($search_engine, $logger);
    $builders[] = new CompaniesSearchBuilder($search_engine, $logger);
    $builders[] = new ProjectsSearchBuilder($search_engine, $logger);

    foreach (['task_lists', 'tasks', 'recurring_tasks', 'discussions', 'files', 'notes'] as $project_elements) {
        $builders[] = new ProjectElementsSearchBuilder(
            $search_engine,
            $logger,
            $project_elements
        );
    }
}
