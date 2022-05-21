<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;

function tasks_handle_on_user_access_search_filter(User $user, BoolQuery &$query)
{
    $project_ids = $user->getProjectIds();

    if (!empty($project_ids)) {
        // will be true if type field is equal to 'tasklist'
        // and one of project ids ar in project_id field
        $task_list_query = new BoolQuery();
        $task_list_query->add(new TermQuery('type', TaskList::class));
        $task_list_query->add(new TermsQuery('project_id', $project_ids));

        // will be true if type field is equal to 'task'
        // and one of project ids ar in project_id field
        $task_query = new BoolQuery();
        $task_query->add(new TermQuery('type', Task::class));
        $task_query->add(new TermsQuery('project_id', $project_ids));

        // and, if user is type of client, is_hidden_from_clients equal to false
        if ($user instanceof Client) {
            $task_query->add(new TermQuery('is_hidden_from_clients', false));
        }

        // add as OR option to passed boolean query
        $query->add($task_list_query, BoolQuery::SHOULD);

        // add as OR option to passed boolean query
        $query->add($task_query, BoolQuery::SHOULD);
    }
}
