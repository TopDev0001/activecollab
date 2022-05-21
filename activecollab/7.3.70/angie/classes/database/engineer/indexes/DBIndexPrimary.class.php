<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Primary index definition.
 *
 * @package angie.library.database
 * @subpackage engineer
 */
class DBIndexPrimary extends DBIndex
{
    /**
     * Construct primary key.
     *
     * @param array $columns
     */
    public function __construct($columns)
    {
        parent::__construct('PRIMARY', DBIndex::PRIMARY, $columns);
    }
}
