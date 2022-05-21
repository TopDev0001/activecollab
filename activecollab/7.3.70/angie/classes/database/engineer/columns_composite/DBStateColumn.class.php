<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

/**
 * @deprecated
 */
class DBStateColumn extends DBCompositeColumn
{
    public function __construct(int $default = 0)
    {
        $this->columns = [
            DBIntegerColumn::create('state', 3, $default)->setUnsigned(true)->setSize(DBColumn::TINY),
            DBIntegerColumn::create('original_state', 3)->setUnsigned(true)->setSize(DBColumn::TINY),
        ];
    }
}
