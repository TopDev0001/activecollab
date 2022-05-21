<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\IgnoredDomainsResolver;

interface IgnoredDomainsResolverInterface
{
    public function isDomainIgnored(string $domain): bool;
}
