<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls;

abstract class Url implements UrlInterface
{
    private string $url;
    private ?array $parsed_url = null;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getExtendedUrl(array $extend_with): string
    {
        if (empty($extend_with)) {
            return $this->url;
        }

        $parsed_url = $this->getParsedUrl();

        return sprintf(
            '%s://%s%s%s%s%s%s',
            $parsed_url['scheme'],
            !empty($parsed_url['user']) && array_key_exists('pass', $parsed_url)
                ? sprintf('%s:%s@', $parsed_url['user'], $parsed_url['pass'])
                : '',
            $parsed_url['host'],
            !empty($parsed_url['port']) ? sprintf(':%d', $parsed_url['port']) : '',
            !empty($parsed_url['path']) ? '/' . ltrim($parsed_url['path'], '/') : '',
            $this->prepareQueryString($parsed_url, $extend_with),
            !empty($parsed_url['fragment']) ? sprintf('#%s', $parsed_url['fragment']) : '',
        );
    }

    public function removeQueryElement(string $query_element_name): string
    {
        if (empty($query_element_name)) {
            return $this->url;
        }

        $parsed_url = $this->getParsedUrl();
        $query = [];

        if (isset($parsed_url) && !empty($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query);
            if (isset($query[$query_element_name])) {
                unset($query[$query_element_name]);
            }
        }

        return sprintf(
            '%s%s%s%s%s%s%s',
            !empty($parsed_url['scheme']) ? sprintf('%s://', $parsed_url['scheme']) : '',
            !empty($parsed_url['user']) && array_key_exists('pass', $parsed_url)
                ? sprintf('%s:%s@', $parsed_url['user'], $parsed_url['pass'])
                : '',
            !empty($parsed_url['host']) ? $parsed_url['host'] : '',
            !empty($parsed_url['port']) ? sprintf(':%d', $parsed_url['port']) : '',
            !empty($parsed_url['path']) ? '/' . ltrim($parsed_url['path'], '/') : '',
            !empty($query) ? '?' . http_build_query($query) : '',
            !empty($parsed_url['fragment']) ? sprintf('#%s', $parsed_url['fragment']) : '',
        );
    }

    private function prepareQueryString(array $paresed_url, array $extend_with): string
    {
        if (!empty($paresed_url['query'])) {
            $query = [];
            parse_str($paresed_url['query'], $query);

            $query = array_merge($query, $extend_with);
        } else {
            $query = $extend_with;
        }

        return '?' . http_build_query($query);
    }

    public function __toString(): string
    {
        return $this->url;
    }

    protected function getParsedUrl(): array
    {
        if (empty($this->parsed_url)) {
            $this->parsed_url = parse_url($this->getUrl());

            if (!is_array($this->parsed_url)) {
                $this->parsed_url = [];
            }
        }

        return $this->parsed_url;
    }
}
