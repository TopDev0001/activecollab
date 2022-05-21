<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class HideFromClientsFeature extends Feature implements HideFromClientsFeatureInterface
{
    public function getName(): string
    {
        return HideFromClientsFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return HideFromClientsFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'hide_from_clients_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'hide_from_clients_enabled_lock';
    }
}
