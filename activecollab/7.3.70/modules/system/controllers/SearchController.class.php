<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Search\SearchResult\SearchResultInterface;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class SearchController extends AuthRequiredController
{
    /**
     * Query the index.
     *
     * @return SearchResultInterface
     */
    public function query(Request $request, User $user)
    {
        return AngieApplication::search()->query(
            $request->get('q'),
            $user,
            AngieApplication::search()->getCriterionsFromRequest($request->get()),
            $request->getPage(),
            100
        );
    }
}
