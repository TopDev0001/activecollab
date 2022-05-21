<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\GhostTasks\Resolver;

use DateValue;

interface GhostTasksResolverInterface
{
    public function getForCalendar(array $ids, DateValue $from_date, DateValue $to_date): array;
}
