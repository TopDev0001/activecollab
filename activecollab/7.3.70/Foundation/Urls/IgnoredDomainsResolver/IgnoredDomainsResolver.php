<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\IgnoredDomainsResolver;

class IgnoredDomainsResolver implements IgnoredDomainsResolverInterface
{
    private $ignored_domains;

    public function __construct(string ...$ignored_domains)
    {
        $this->ignored_domains = $ignored_domains;
    }

    public function isDomainIgnored(string $domain): bool
    {
        return in_array($domain, $this->ignored_domains);
    }
}
