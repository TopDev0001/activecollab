<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Utils\FeatureStatusResolver;

use Angie\Features\FeatureInterface;

class SelfHostedFeatureStatusResolver implements FeatureStatusResolverInterface
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
