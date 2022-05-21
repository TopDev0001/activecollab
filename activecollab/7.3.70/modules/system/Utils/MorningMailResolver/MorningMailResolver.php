<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\MorningMailResolver;

use AccountSettingsInterface;
use ActiveCollab\Module\System\Utils\MorningMailManager\CloudMorningMailManager;
use ActiveCollab\Module\System\Utils\MorningMailManager\MorningMailManagerInterface;
use ActiveCollab\Module\System\Utils\MorningMailManager\SelfHostedMorningMailManager;

class MorningMailResolver implements MorningMailResolverInterface
{
    private bool $is_ondemand;
    private ?AccountSettingsInterface $account_settings;

    public function __construct(
        bool $is_ondemand,
        ?AccountSettingsInterface $account_settings
    )
    {
        $this->is_ondemand = $is_ondemand;
        $this->account_settings = $account_settings;
    }

    public function getMorningMailManager(): MorningMailManagerInterface
    {
        if ($this->is_ondemand && $this->account_settings) {
            return new CloudMorningMailManager($this->account_settings->getAccountStatus());
        }

        return new SelfHostedMorningMailManager();
    }
}
