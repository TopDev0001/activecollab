<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Collections\ActivityLog;

use ActiveCollab\Module\System\Collections\ActivityLog\Traits\IActivityLogsCollection;
use ActiveCollab\Module\System\Collections\ActivityLog\Traits\IRangeDates;
use CompositeCursorCollection;
use CursorModelCollection;
use IWhosAsking;

abstract class BaseActivityLogsCursorCollection extends CompositeCursorCollection
{
    use IActivityLogsCollection;
    use IWhosAsking;
    use IRangeDates;

    protected CursorModelCollection $activity_logs_collection;

    public function getCursorCollection(): CursorModelCollection
    {
        return $this->getActivityLogsCollection();
    }
}
