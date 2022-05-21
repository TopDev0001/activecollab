<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\PushNotification;

use DateTimeInterface;

interface PushScheduleDaysOffResolverInterface
{
    public function isDayOff(DateTimeInterface $date_time): bool;
}
