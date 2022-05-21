<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue;

use LogicException;

trait DateRangeTrait
{
    private $start_date;
    private $end_date;
    private $start_date_time;
    private $end_date_time;

    protected function setStartAndEndDate(DateValueInterface $start_date, DateValueInterface $end_date)
    {
        if ($end_date->getTimestamp() < $start_date->getTimestamp()) {
            throw new LogicException("End date can't be before start date.");
        }

        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->start_date_time = new DateTimeValue($this->start_date->format('Y-m-d 00:00:00'));
        $this->end_date_time = new DateTimeValue($this->end_date->format('Y-m-d 23:59:59'));
    }

    public function getStartDate(): DateValueInterface
    {
        return $this->start_date;
    }

    public function getEndDate(): DateValueInterface
    {
        return $this->end_date;
    }

    public function getStartDateTime(): DateTimeValueInterface
    {
        return $this->start_date_time;
    }

    public function getEndDateTime(): DateTimeValueInterface
    {
        return $this->end_date_time;
    }

    public function getIterator()
    {
        return new DateRangeIterator($this);
    }

    public function jsonSerialize()
    {
        return [
            'type' => get_class($this),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
