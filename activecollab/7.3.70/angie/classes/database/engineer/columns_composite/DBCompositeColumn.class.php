<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Composite columns are columns that are made of multiple fields.
 *
 * @package angie.library.database
 * @subpackage engineer
 */
abstract class DBCompositeColumn
{
    /**
     * Parent table.
     *
     * @var DBTable
     */
    protected $table;

    /**
     * Array of columns that make this composite column.
     *
     * @var DBColumn[]
     */
    protected $columns = [];

    /**
     * Return array of columns that need to be added to the table.
     *
     * @return DBColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function addedToTable(): void
    {
    }

    /**
     * Return parent table instance.
     *
     * @return DBTable
     */
    public function &getTable()
    {
        return $this->table;
    }

    /**
     * Set parent table.
     *
     * @return DBCompositeColumn
     */
    public function &setTable(DBTable &$table)
    {
        $this->table = $table;

        return $this;
    }
}
