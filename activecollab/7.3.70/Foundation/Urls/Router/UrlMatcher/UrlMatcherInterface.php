<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\UrlMatcher;

use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRouteInterface;

interface UrlMatcherInterface
{
    const MATCH_ID = '\d+';
    const MATCH_WORD = '\w+';
    const MATCH_SLUG = '[a-z0-9\-\._]+';
    const MATCH_HASH = '[a-z0-9]{40}';
    const MATCH_DATE = '([0-9]{2,4})-([0-1][0-9])-([0-3][0-9])';

    public function match(string $path_string, string $query_string): ?MatchedRouteInterface;
    public function mustMatch(string $path_string, string $query_string): MatchedRouteInterface;
    public function matchUrl(string $url): ?MatchedRouteInterface;
}
