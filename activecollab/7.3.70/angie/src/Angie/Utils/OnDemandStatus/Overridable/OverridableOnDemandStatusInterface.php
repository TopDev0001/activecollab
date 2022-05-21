<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\OnDemandStatus\Overridable;

use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;

interface OverridableOnDemandStatusInterface extends OnDemandStatusInterface
{
    public function resetToDefault();
    public function forceValue(bool $forced_value);
}
