<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Search\HostsResolver;

final class TestHostsResolver extends HostsResolver
{
    public function __construct()
    {
        parent::__construct('');
    }

    public function getHosts(): array
    {
        return ['http://localhost:9200'];
    }
}
