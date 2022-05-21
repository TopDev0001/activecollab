<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Features;

use Angie\Features\FeatureInterface;

interface ExpenseTrackingFeatureInterface extends FeatureInterface
{
    const NAME = 'expense_tracking';
    const VERBOSE_NAME = 'Expense Tracking';
}
