<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * MySQL database result.
 *
 * @package angie.library.database
 * @subpackage mysql
 */
final class MySQLDBResult extends DBResult
{
    /**
     * Result resource.
     *
     * @var mysqli_result
     */
    protected $resource;

    /**
     * Set cursor to a given position in the record set.
     *
     * @param  int  $num
     * @return bool
     */
    public function seek($num)
    {
        if ($num >= 0 && $num <= $this->count() - 1) {
            if (!$this->resource->data_seek($num)) {
                return false;
            }

            $this->cursor_position = $num;

            return true;
        }

        return false;
    }

    /**
     * Return number of records in result set.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->resource->num_rows;
    }

    /**
     * Return next record in result set.
     *
     * @return array
     * @throws DBError
     */
    public function next()
    {
        if ($this->cursor_position < $this->count() && $row = $this->resource->fetch_assoc()) { // Not count() - 1 because we use this for getting the current row
            $this->setCurrentRow($row);
            ++$this->cursor_position;

            return true;
        }

        return false;
    }

    /**
     * Free resource when we are done with this result.
     *
     * @return bool
     */
    public function free()
    {
        if ($this->resource instanceof mysqli_result) {
            $this->resource->close();
        }
    }

    /**
     * Returns true if $resource is valid result resource.
     *
     * @param  mixed $resource
     * @return bool
     */
    protected function isValidResource($resource)
    {
        return $resource instanceof mysqli_result && $resource->num_rows > 0;
    }
}
