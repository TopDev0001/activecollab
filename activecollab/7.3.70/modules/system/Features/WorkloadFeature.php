<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Features;

use ActiveCollab\Module\OnDemand\Model\AddOn\AddOnInterface;
use Angie\Features\Feature;

class WorkloadFeature extends Feature implements WorkloadFeatureInterface
{
    public function getName(): string
    {
        return WorkloadFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return WorkloadFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [AddOnInterface::ADD_ON_GET_PAID];
    }

    public function getIsEnabledFlag(): string
    {
        return 'workload_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'workload_enabled_lock';
    }
}
