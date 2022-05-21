<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Firewall;

interface FirewallInterface
{
    public function getWhiteList(): array;
    public function getBlackList(): array;

    public function shouldBlock(IpAddressInterface $ip_address): bool;
    public function isOnWhiteList(IpAddressInterface $ip_address): bool;
    public function isOnBlackList(IpAddressInterface $ip_address): bool;
}
