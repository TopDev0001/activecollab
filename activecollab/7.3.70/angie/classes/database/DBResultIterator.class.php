<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Class that lets PHP natively iterate over DB results.
 *
 * @package angie.library.database
 */
class DBResultIterator implements Iterator
{
    /**
     * Result set that is iterated.
     *
     * @var DBResult
     */
    private $result;

    /**
     * Construct the iterator.
     */
    public function __construct(DBResult $result)
    {
        $this->result = $result;
    }

    /**
     * If not at start of resultset, this method will call seek(0).
     *
     * @see ResultSet::seek()
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        if ($this->result->getCursorPosition() > 0) {
            $this->result->seek(0);
        }
    }

    /**
     * This method checks to see whether there are more results
     * by advancing the cursor position.
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->result->next();
    }

    /**
     * Returns the cursor position.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->result->getCursorPosition();
    }

    /**
     * Returns the row (assoc array) at current cursor position.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->result->getCurrentRow();
    }

    /**
     * This method does not actually do anything since we have already advanced
     * the cursor pos in valid().
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
    }
}
