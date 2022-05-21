<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\FeatureFlags;

class TestFeatureFlags implements FeatureFlagsInterface
{
    private array $feature_flags = [];

    public function setFeatureFlags(array $feature_flags): FeatureFlagsInterface
    {
        $this->feature_flags = $feature_flags;

        return $this;
    }

    public function getFeatureFlags(): array
    {
        return $this->feature_flags;
    }

    public function isEnabled(string $feature_flag): bool
    {
        return !in_array($feature_flag, ['redis_cache', 'redis_cache_production']); //@ToDo remove when fix redis driver
    }

    public function jsonSerialize(): array
    {
        return $this->getFeatureFlags();
    }
}
