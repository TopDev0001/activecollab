<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use Angie\Globalization;
use DateTimeInterface;
use DateValue;

class PushScheduleDaysOffResolver implements PushScheduleDaysOffResolverInterface
{
    private array $memory = []; //cached values

    public function isDayOff(DateTimeInterface $date_time): bool
    {
        $date_value = DateValue::makeFromString($date_time->format('Y-m-d'));
        if (isset($this->memory[$date_value->toMySQL()])) {
            return $this->memory[$date_value->toMySQL()];
        }
        //TODO: potential performance issues - find better way
        $result = Globalization::isDayOff($date_value);
        $this->memory[$date_value->toMySQL()] = $result;

        return $result;
    }
}
