<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class ExpenseTrackingFeature extends Feature implements ExpenseTrackingFeatureInterface
{
    public function getName(): string
    {
        return ExpenseTrackingFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return ExpenseTrackingFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'expense_tracking_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'expense_tracking_enabled_lock';
    }
}
