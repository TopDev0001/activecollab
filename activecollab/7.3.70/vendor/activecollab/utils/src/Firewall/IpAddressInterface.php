<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Firewall;

interface IpAddressInterface
{
    public function isOnList(array $list): bool;
}
