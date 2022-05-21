<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\AdapterResolver;

use Angie\Storage\StorageAdapterInterface;
use IntegrationInterface;

interface StorageAdapterResolverInterface
{
    public function getByIntegration(IntegrationInterface $integration): StorageAdapterInterface;
}
