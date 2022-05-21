<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\OnDemandStatus;

class OnDemandStatus implements OnDemandStatusInterface
{
    private $is_on_demand;

    public function __construct(bool $is_on_demand)
    {
        $this->is_on_demand = $is_on_demand;
    }

    public function isOnDemand(): bool
    {
        return $this->is_on_demand;
    }
}
