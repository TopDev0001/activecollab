<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\UrlMatcher;

use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRouteInterface;
use Angie\Error;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class UrlMatcher implements UrlMatcherInterface
{
    protected $root_url;
    protected $logger;

    public function __construct(RootUrlInterface $root_url, LoggerInterface $logger)
    {
        $this->root_url = $root_url;
        $this->logger = $logger;
    }

    public function mustMatch(string $path_string, string $query_string): MatchedRouteInterface
    {
        $matched_route = $this->match($path_string, $query_string);

        if (empty($matched_route)) {
            throw new RuntimeException(sprintf("Failed to match '%s' path.", strip_tags($path_string)));
        }

        return $matched_route;
    }

    public function matchUrl(string $url): ?MatchedRouteInterface
    {
        [
            $path_string,
            $query_string,
        ] = $this->extractPathAndQueryString($url);

        return $this->match($path_string, $query_string);
    }

    protected function valuesFromMatchedPath(
        array $parameters,
        array $defaults,
        array $matches,
        string $query_string
    ): array
    {
        $values = $defaults;

        // Match variables from path
        $index = 0;
        foreach ($parameters as $parameter_name) {
            ++$index;

            if ($parameter_name == 'id' || str_ends_with($parameter_name, '_id')) {
                $values[$parameter_name] = (int) $matches[$index];
            } else {
                $values[$parameter_name] = $matches[$index];
            }
        }

        if ($query_string) {
            $reserved = [
                'module',
                'controller',
                'action',
            ];

            $query_string_parameters = [];
            parse_str($query_string, $query_string_parameters);

            if (is_foreachable($query_string_parameters)) {
                foreach ($query_string_parameters as $parameter_name => $parameter_value) {
                    if (isset($values[$parameter_name]) && in_array($values[$parameter_name], $reserved)) {
                        continue;
                    }

                    $values[$parameter_name] = $parameter_value;
                }
            }
        }

        return $values;
    }

    private function extractPathAndQueryString(string $url): array
    {
        $query_string = '';

        if (!$this->root_url->isInternalUrl($url)) {
            throw new Error(sprintf('Value "%s" is not an internal URL.', $url));
        }

        $useful_part = mb_substr($url, mb_strlen($this->root_url->getUrl()));
        if ($useful_part[0] == '/') {
            $useful_part = mb_substr($useful_part, 1);
        }

        $question_mark_pos = mb_strpos($useful_part, '?');

        if ($question_mark_pos !== false) {
            $path_info = mb_substr($useful_part, 0, $question_mark_pos);
        } else {
            $path_info = $useful_part;
        }

        if ($path_info && $path_info[mb_strlen($path_info) - 1] == '/') {
            $path_info = mb_substr($path_info, 0, -1);
        }

        if ($question_mark_pos !== false) {
            $query_string = mb_substr($useful_part, $question_mark_pos + 1);
        }

        return [
            $path_info,
            $query_string,
        ];
    }
}
