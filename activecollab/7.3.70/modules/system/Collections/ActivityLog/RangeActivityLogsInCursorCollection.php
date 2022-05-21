<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Collections\ActivityLog;

use ActiveCollab\Module\System\Collections\ActivityLog\Traits\ILogsIn;
use ActivityLogs;
use ApplicationObject;
use CursorModelCollection;
use ImpossibleCollectionError;
use User;

class RangeActivityLogsInCursorCollection extends BaseActivityLogsCursorCollection
{
    use ILogsIn;

    public function getModelName(): string
    {
        return $this->in->getModelName();
    }

    public function getTimestampHash(): string
    {
        return sha1(
            sprintf(
                '%s,%s',
                $this->in->getUpdatedOn()->toMySQL(),
                $this->getActivityLogsCollection()->getTimestampHash('updated_on')
            )
        );
    }

    protected function &getActivityLogsCollection(): CursorModelCollection
    {
        if (empty($this->activity_logs_collection)) {
            if ($this->checkCollectionNameValues()) {
                $this->activity_logs_collection = ActivityLogs::prepareCursorCollection(
                    sprintf(
                        'range_activity_logs_in_%s_%s',
                        get_class($this->in) . '-' . $this->in->getId(),
                        $this->dates
                    ),
                    $this->getWhosAsking()
                );
            } else {
                throw new ImpossibleCollectionError("Invalid in and/or who's asking instance");
            }
        }

        return $this->activity_logs_collection;
    }

    private function checkCollectionNameValues(): bool
    {
        return strpos($this->dates, ':') !== false
            && $this->in instanceof ApplicationObject
            && $this->getWhosAsking() instanceof User;
    }
}
