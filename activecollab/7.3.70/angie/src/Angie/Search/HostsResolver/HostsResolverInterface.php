<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Search\HostsResolver;

interface HostsResolverInterface
{
    public function getHosts(): array;
}
