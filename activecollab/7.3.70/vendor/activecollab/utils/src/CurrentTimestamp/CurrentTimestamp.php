<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\CurrentTimestamp;

class CurrentTimestamp implements CurrentTimestampInterface
{
    public function getCurrentTimestamp(): int
    {
        return time();
    }
}
