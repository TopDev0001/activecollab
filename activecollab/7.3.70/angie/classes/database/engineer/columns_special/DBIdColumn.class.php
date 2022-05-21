<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBIdColumn extends DBIntegerColumn
{
    /**
     * Create new ID column.
     *
     * @param int|string $length
     */
    public function __construct($length = DBColumn::NORMAL)
    {
        parent::__construct('id', $length, 0);

        $this->setUnsigned(true);
        $this->setAutoIncrement(true);
    }
}
