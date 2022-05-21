<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue;

use Carbon\Carbon;
use DateTimeInterface;

class DateValue extends Carbon implements DateValueInterface
{
    /**
     * Create a new DateTimeValue instance.
     *
     * @param DateTimeInterface|string|null $time
     * @param \DateTimeZone|string          $tz
     */
    public function __construct($time = null, $tz = null)
    {
        if ($time instanceof DateTimeInterface) {
            $create_from = $time->format('Y-m-d');
        } else {
            $create_from = $time;
        }

        if (empty($tz)) {
            $tz = 'UTC';
        }

        parent::__construct($create_from, $tz);

        $this->startOfDay();
    }

    public function jsonSerialize()
    {
        return $this->getTimestamp();
    }
}
