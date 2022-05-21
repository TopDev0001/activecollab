<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\Cache\PoolFactory;

use Stash\Pool;

interface CachePoolFactoryInterface
{
    public function createPool(): Pool;
}
