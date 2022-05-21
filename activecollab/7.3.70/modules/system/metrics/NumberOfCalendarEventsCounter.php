<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Metric;

use Angie\Metric\Counter;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use DB;

final class NumberOfCalendarEventsCounter extends Counter
{
    public function getValueFor(DateValue $date): ResultInterface
    {
        return $this->produceResult(
            (int) DB::executeFirstCell(
                'SELECT COUNT(id) AS "row_count" FROM calendar_events WHERE is_trashed = ?',
                false
            ),
            $date
        );
    }
}
