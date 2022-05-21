<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBIpAddressColumn extends DBStringColumn
{
    public function __construct(string $name, string $default = null)
    {
        parent::__construct($name, $default);

        $this->setLength(45);
    }
}
