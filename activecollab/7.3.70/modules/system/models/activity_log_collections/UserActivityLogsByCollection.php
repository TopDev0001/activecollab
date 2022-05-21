<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class UserActivityLogsByCollection extends UserActivityLogsCollection
{
    /**
     * @var ModelCollection
     */
    private $activity_logs_collection;

    /**
     * Return assigned tasks collection.
     *
     * @return ModelCollection
     * @throws ImpossibleCollectionError
     */
    protected function &getActivityLogsCollection()
    {
        if (empty($this->activity_logs_collection)) {
            if ($this->getForOrBy() instanceof User && $this->getWhosAsking() instanceof User) {
                $this->activity_logs_collection = ActivityLogs::prepareCollection('activity_logs_by_' . $this->getForOrBy()->getId() . '_page_' . $this->getCurrentPage(), $this->getWhosAsking());
            } else {
                throw new ImpossibleCollectionError("Invalid user and/or who's asking instance");
            }
        }

        return $this->activity_logs_collection;
    }
}
