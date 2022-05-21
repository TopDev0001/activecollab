<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls;

class ExternalUrl extends Url implements ExternalUrlInterface
{
    private ?string $domain;

    public function __construct(string $url, string $domain = null)
    {
        parent::__construct($url);

        $this->domain = $domain;
    }

    public function getDomain(): string
    {
        if ($this->domain === null) {
            $parsed_url = $this->getParsedUrl();

            $this->domain = array_key_exists('host', $parsed_url) && is_string($parsed_url['host'])
                ? $parsed_url['host']
                : '';

            if (str_starts_with($this->domain, 'www.')) {
                $this->domain = mb_substr($this->domain, 4);
            }
        }

        return $this->domain;
    }
}
