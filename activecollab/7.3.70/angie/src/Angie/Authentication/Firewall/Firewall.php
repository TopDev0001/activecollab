<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Authentication\Firewall;

use ActiveCollab\Firewall\Firewall as BaseFirewall;
use ActiveCollab\Firewall\IpAddressInterface;

class Firewall extends BaseFirewall
{
    private bool $is_enabled;

    public function __construct(
        bool $is_enabled,
        array $white_list,
        array $black_list,
        bool $validate_rules = true
    )
    {
        parent::__construct($white_list, $black_list, $validate_rules);

        $this->is_enabled = $is_enabled;
    }

    public function shouldBlock(IpAddressInterface $ip_address): bool
    {
        if ($this->is_enabled) {
            return parent::shouldBlock($ip_address);
        }

        return false;
    }
}
