<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\Maintenance;

use DateValue;

class DailyMaintenanceEvent extends MaintenanceEvent implements DailyMaintenanceEventInterface
{
    private $day;

    public function __construct(DateValue $day)
    {
        $this->day = $day;
    }

    public function getDay(): DateValue
    {
        return $this->day;
    }
}
