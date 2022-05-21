<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\FeatureFlags;

use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;

class FeatureFlagsStringResolver implements FeatureFlagsStringResolverInterface
{
    private ApplicationModeInterface $application_mode;
    private OnDemandStatusInterface $on_demand_status;

    public function __construct(
        ApplicationModeInterface $application_mode,
        OnDemandStatusInterface $on_demand_status
    )
    {
        $this->application_mode = $application_mode;
        $this->on_demand_status = $on_demand_status;
    }

    public function getString(): string
    {
        if ($this->on_demand_status->isOnDemand() && $this->application_mode->isInProduction()) {
            return (string) getenv('ACTIVECOLLAB_FEATURE_FLAGS');
        } elseif ($this->application_mode->isInDevelopment()) {
            return IS_LEGACY_DEV
                ? implode(',', defined('ACTIVECOLLAB_FEATURE_FLAGS') ? ACTIVECOLLAB_FEATURE_FLAGS : [])
                : (string) getenv('ACTIVECOLLAB_FEATURE_FLAGS');
        }

        return '';
    }
}
