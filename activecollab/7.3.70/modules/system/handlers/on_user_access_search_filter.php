<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;

function system_handle_on_user_access_search_filter(User $user, BoolQuery &$query): void
{
    // will be true if type field is equal to 'user'
    // and one of visible user ids ar in id field
    $visible_user_ids_query = new BoolQuery();
    $visible_user_ids_query->add(
        new TermsQuery(
            'type',
            [
                Owner::class,
                Member::class,
                Client::class,
            ]
        )
    );
    $visible_user_ids_query->add(new TermsQuery('id', $user->getVisibleUserIds()));

    $query->add($visible_user_ids_query, BoolQuery::SHOULD);

    $project_ids = $user->getProjectIds();

    if (!empty($project_ids)) {
        // will be true if type field is equal to 'project'
        // and one of project ids ar in id field
        $project_query = new BoolQuery();
        $project_query->add(new TermQuery('type', Project::class));
        $project_query->add(new TermsQuery('id', $project_ids));

        // add as OR option to passed boolean query
        $query->add($project_query, BoolQuery::SHOULD);

        foreach (Projects::getAvailableProjectElementClasses() as $project_element_type) {
            // will be true if type field is equal to project element type
            // and one of project ids ar in project_id field
            $project_element_query = new BoolQuery();
            $project_element_query->add(new TermQuery('type', $project_element_type));
            $project_element_query->add(new TermsQuery('project_id', $project_ids));

            $reflection = new ReflectionClass($project_element_type);
            // and, if user is type of client, is_hidden_from_clients equal to false
            if ($user instanceof Client && $reflection->implementsInterface(IHiddenFromClients::class)) {
                $project_element_query->add(new TermQuery('is_hidden_from_clients', false));
            }

            // add as OR option to passed boolean query
            $query->add($project_element_query, BoolQuery::SHOULD);
        }
    }

    // will be true if type field is equal to 'company'
    // and one of visible company ids ar in id field
    $company_query = new BoolQuery();
    $company_query->add(new TermQuery('type', Company::class));
    $company_query->add(new TermsQuery('id', $user->getVisibleCompanyIds()));

    // add as OR option to passed boolean query
    $query->add($company_query, BoolQuery::SHOULD);
}
