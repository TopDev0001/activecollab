<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\FeatureStatusResolver;

use Angie\Features\FeatureInterface;

interface FeatureStatusResolverInterface
{
    public function isEnabled(FeatureInterface $feature): bool;
    public function isLocked(FeatureInterface $feature): bool;
}
