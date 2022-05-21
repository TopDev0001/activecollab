<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\FeatureFlags;

use JsonSerializable;

interface FeatureFlagsInterface extends JsonSerializable
{
    const EDGE_CHANNEL_MODIFIER = 'edge';
    const BETA_CHANNEL_MODIFIER = 'beta';
    const STABLE_CHANNEL_MODIFIER = 'stable';

    const CHANNEL_MODIFIERS = [
        self::EDGE_CHANNEL_MODIFIER,
        self::BETA_CHANNEL_MODIFIER,
        self::STABLE_CHANNEL_MODIFIER,
    ];

    public function getFeatureFlags(): array;
    public function isEnabled(string $feature_flag): bool;
}
