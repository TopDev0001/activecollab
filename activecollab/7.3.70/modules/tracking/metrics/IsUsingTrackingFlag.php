<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Metric;

use Angie\Metric\Flag;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use DB;

class IsUsingTrackingFlag extends Flag
{
    public function getValueFor(DateValue $date): ResultInterface
    {
        $last_30_days = $date->addDays(-30, false);
        $last_365_days = $date->addDays(-365, false);

        if (DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM time_records WHERE record_date >= ?', $last_30_days) > 1) {
            $result = DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM time_records WHERE record_date >= ?', $last_365_days) > 5;
        } else {
            $result = false;
        }

        return $this->produceResult($result, $date);
    }
}
