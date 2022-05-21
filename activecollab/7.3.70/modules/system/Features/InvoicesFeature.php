<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use ActiveCollab\Module\OnDemand\Model\AddOn\AddOnInterface;
use Angie\Features\Feature;

class InvoicesFeature extends Feature implements InvoicesFeatureInterface
{
    public function getName(): string
    {
        return InvoicesFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return InvoicesFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [AddOnInterface::ADD_ON_GET_PAID];
    }

    public function getIsEnabledFlag(): string
    {
        return 'invoices_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'invoices_enabled_lock';
    }
}
