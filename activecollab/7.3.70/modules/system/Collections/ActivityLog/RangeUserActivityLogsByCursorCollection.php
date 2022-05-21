<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Collections\ActivityLog;

use ActivityLogs;
use CursorModelCollection;
use ImpossibleCollectionError;

class RangeUserActivityLogsByCursorCollection extends BaseUserActivityLogsCursorCollection
{
    protected function &getActivityLogsCollection(): CursorModelCollection
    {
        if (empty($this->activity_logs_collection)) {
            if ($this->checkCollectionNameValues()) {
                $this->activity_logs_collection = ActivityLogs::prepareCursorCollection(
                    sprintf(
                        'range_activity_logs_by_%s_%s',
                        $this->getForOrBy()->getId(),
                        $this->dates
                    ),
                    $this->getWhosAsking()
                );
            } else {
                throw new ImpossibleCollectionError(
                    "Invalid user and/or date range and/or who's asking instance"
                );
            }
        }

        return $this->activity_logs_collection;
    }
}
