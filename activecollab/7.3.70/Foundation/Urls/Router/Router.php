<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router;

use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRouteInterface;
use ActiveCollab\Foundation\Urls\Router\UrlAssembler\UrlAssemblerInterface;
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use Psr\Log\LoggerInterface;

class Router implements RouterInterface
{
    private UrlAssemblerInterface $url_assembler;
    private UrlMatcherInterface $url_matcher;
    private LoggerInterface $logger;

    public function __construct(
        UrlAssemblerInterface $url_assembler,
        UrlMatcherInterface $url_matcher,
        LoggerInterface $logger
    )
    {
        $this->url_assembler = $url_assembler;
        $this->url_matcher = $url_matcher;
        $this->logger = $logger;
    }

    public function assemble(string $name, array $data = []): string
    {
        return $this->url_assembler->assemble($name, $data);
    }

    public function match(string $path_string, string $query_string): ?MatchedRouteInterface
    {
        return $this->url_matcher->match($path_string, $query_string);
    }

    public function mustMatch(string $path_string, string $query_string): MatchedRouteInterface
    {
        return $this->url_matcher->mustMatch($path_string, $query_string);
    }

    public function matchUrl(string $url): ?MatchedRouteInterface
    {
        return $this->url_matcher->matchUrl($url);
    }
}
