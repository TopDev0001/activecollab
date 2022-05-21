<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\FeatureStatusResolver;

use Angie\Features\FeatureInterface;

class OnDemandFeatureStatusResolver implements FeatureStatusResolverInterface
{
    public function isEnabled(FeatureInterface $feature): bool
    {
        return $feature->isEnabled();
    }

    public function isLocked(FeatureInterface $feature): bool
    {
        return $feature->isLocked();
    }
}
