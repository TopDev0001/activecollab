<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Features;

interface FeatureFactoryInterface
{
    public function getKnownFeatureNames(): iterable;

    /**
     * @return FeatureInterface[]|iterable
     */
    public function getKnownFeatures(): iterable;
    public function makeFeature(string $feature_name): FeatureInterface;
}
