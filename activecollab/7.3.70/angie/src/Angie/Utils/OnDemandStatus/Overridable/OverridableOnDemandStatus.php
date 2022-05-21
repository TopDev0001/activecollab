<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\OnDemandStatus\Overridable;

class OverridableOnDemandStatus implements OverridableOnDemandStatusInterface
{
    private $default_value;
    private $forced_value;

    public function __construct(bool $default_is_on_demand_value)
    {
        $this->default_value = $default_is_on_demand_value;
    }

    public function isOnDemand(): bool
    {
        if ($this->forced_value !== null) {
            return $this->forced_value;
        }

        return $this->default_value;
    }

    public function resetToDefault()
    {
        $this->forced_value = null;
    }

    public function forceValue(bool $forced_value)
    {
        $this->forced_value = $forced_value;
    }
}
