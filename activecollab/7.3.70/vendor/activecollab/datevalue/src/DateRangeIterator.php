<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue;

use Iterator;

class DateRangeIterator implements Iterator
{
    private $date_range;

    private $current_index = 0;

    /**
     * @var DateValue
     */
    private $current_date;

    private $end_date_timestamp;

    public function __construct(DateRangeInterface $date_range)
    {
        $this->date_range = $date_range;
        $this->end_date_timestamp = $this->date_range->getEndDate()->format('Y-m-d');
    }

    public function rewind()
    {
        $this->current_index = null;
        $this->current_date = null;
    }

    public function next()
    {
//        $this->current_date = $this->getNextDate();
//        $this->current_index++;
    }

    public function valid()
    {
        if ($this->current_date === null && $this->current_index === null) {
            return true;
        }

        if (strcmp($this->getNextDate()->format('Y-m-d'), $this->end_date_timestamp) <= 0) {
            return true;
        } else {
            return false;
        }
    }

    public function key()
    {
        return $this->current_index;
    }

    public function current()
    {
        $this->current_date = $this->getNextDate();

        if ($this->current_index === null) {
            $this->current_index = 0;
        } else {
            $this->current_index++;
        }

        return $this->current_date;
    }

    private function getNextDate(): DateValueInterface
    {
        if ($this->current_date === null) {
            return clone $this->date_range->getStartDate();
        } else {
            return (clone $this->current_date)->addDay(1);
        }
    }
}
