<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBMoneyColumn extends DBDecimalColumn
{
    public function __construct(string $name, $default = null)
    {
        parent::__construct($name, 13, 3, $default);
    }
}
