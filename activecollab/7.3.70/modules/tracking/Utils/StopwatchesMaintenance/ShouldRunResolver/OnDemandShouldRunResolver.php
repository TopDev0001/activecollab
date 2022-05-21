<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\StopwatchesMaintenance\ShouldRunResolver;

use ActiveCollab\Module\OnDemand\Utils\AccountSettingsManager\AccountSettingsManagerInterface;

class OnDemandShouldRunResolver extends ShouldRunResolver
{
    private AccountSettingsManagerInterface $account_settings_manager;

    public function __construct(AccountSettingsManagerInterface $account_settings_manager)
    {
        $this->account_settings_manager = $account_settings_manager;
    }

    public function shouldRun(
        array $stopwatches_for_daily,
        array $stopwatches_for_maximum
    ): bool
    {
        $account_status = $this->account_settings_manager
            ->getAccountSettings()
                ->getAccountStatus();

        if ($account_status->isSuspended() || $account_status->isRetired()) {
            return false;
        }

        return parent::shouldRun(
            $stopwatches_for_daily,
            $stopwatches_for_maximum
        );
    }
}
