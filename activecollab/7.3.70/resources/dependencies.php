<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\OnDemand\Utils\RetirementEndDateResolver\RetirementEndDateResolver;
use ActiveCollab\Module\OnDemand\Utils\RetirementEndDateResolver\RetirementEndDateResolverInterface;

return [
    RetirementEndDateResolverInterface::class => function ()
    {
        return new RetirementEndDateResolver(
            SuspendAccountSubscriptionServiceInterface::SUSPEND_TIME_PAID,
            SuspendAccountSubscriptionServiceInterface::SUSPEND_TIME_TRIAL
        );
    },
];
