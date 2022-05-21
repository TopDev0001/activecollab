<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class BudgetingFeature extends Feature implements InvoicesFeatureInterface
{
    public function getName(): string
    {
        return BudgetingFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return BudgetingFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'budgeting_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'budgeting_enabled_lock';
    }
}
