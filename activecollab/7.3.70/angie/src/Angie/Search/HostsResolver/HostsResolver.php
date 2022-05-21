<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Search\HostsResolver;

class HostsResolver implements HostsResolverInterface
{
    private $hosts_string;

    public function __construct(string $hosts_string)
    {
        $this->hosts_string = $hosts_string;
    }

    public function getHosts(): array
    {
        $result = [];

        foreach (explode(',', $this->hosts_string) as $host) {
            if ($host = trim($host)) {
                $parts = parse_url($host);

                if ($this->isOnlyHost($parts)) {
                    $result[] = "http://{$host}:9200";
                } elseif ($this->isAuthWithoutSchema($parts)) {
                    $result[] = $this->partsToHost(parse_url("http://{$host}"));
                } else {
                    $result[] = $this->partsToHost($parts);
                }
            }
        }

        return $result;
    }

    private function isOnlyHost(array $parts): bool
    {
        return count($parts) === 1 && array_key_exists('path', $parts);
    }

    private function isAuthWithoutSchema(array $parts): bool
    {
        return count($parts) === 2
            && !empty($parts['scheme'])
            && !in_array($parts['scheme'], ['http', 'https'])
            && !empty($parts['path'])
            && strpos($parts['path'], '@') !== false;
    }

    private function partsToHost(array $parts): string
    {
        $scheme = !empty($parts['scheme']) ? $parts['scheme'] : 'http';
        $host = !empty($parts['host']) ? $parts['host'] : 'localhost';
        $port = !empty($parts['port']) ? $parts['port'] : 9200;

        if (!empty($parts['user']) && !empty($parts['pass'])) {
            return "{$scheme}://{$parts['user']}:{$parts['pass']}@{$host}:$port";
        } else {
            return "{$scheme}://{$host}:$port";
        }
    }
}
