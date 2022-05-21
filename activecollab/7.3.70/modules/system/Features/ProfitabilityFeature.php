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
use ConfigOptions;

class ProfitabilityFeature extends Feature implements ProfitabilityFeatureInterface
{
    public function getName(): string
    {
        return ProfitabilityFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return ProfitabilityFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [AddOnInterface::ADD_ON_GET_PAID];
    }

    public function activate(): bool
    {
        /*
         * @todo This logic should be reconsidered as it is not in line with how other features work.
         */
        ConfigOptions::setValue($this->getIsEnabledFlag(), true, true);
        ConfigOptions::setValue($this->getIsEnabledLockFlag(), true, true);

        return true;
    }

    public function enable(): bool
    {
        /*
         * @todo This logic should be reconsidered as it is not in line with how other features work.
         */
        ConfigOptions::setValue($this->getIsEnabledFlag(), true, true);
        ConfigOptions::setValue($this->getIsEnabledLockFlag(), true, true);

        return true;
    }

    public function disable(): bool
    {
        /*
         * @todo This logic should be reconsidered as it is not in line with how other features work.
         */
        ConfigOptions::setValue($this->getIsEnabledFlag(), false, true);
        ConfigOptions::setValue($this->getIsEnabledLockFlag(), true, true);

        return true;
    }

    public function getIsEnabledFlag(): string
    {
        return 'profitability_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'profitability_enabled_lock';
    }
}
