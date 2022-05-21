<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\FeatureInterface;

interface ProfitabilityFeatureInterface extends FeatureInterface
{
    const NAME = 'profitability';
    const VERBOSE_NAME = 'Profitability';
}
