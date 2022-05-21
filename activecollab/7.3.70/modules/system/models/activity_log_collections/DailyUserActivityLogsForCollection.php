<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DailyUserActivityLogsForCollection extends UserActivityLogsCollection
{
    /**
     * @var DateValue
     */
    private $day;

    /**
     * @return $this
     */
    public function &setDay(DateValue $day)
    {
        $this->day = $day;

        return $this;
    }

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
            if ($this->day instanceof DateValue && $this->getForOrBy() instanceof User && $this->getWhosAsking() instanceof User) {
                $this->activity_logs_collection = ActivityLogs::prepareCollection('daily_activity_logs_for_' . $this->getForOrBy()->getId() . '_' . $this->day->toMySQL() . '_page_' . $this->getCurrentPage(), $this->getWhosAsking());
            } else {
                throw new ImpossibleCollectionError("Invalid user and/or who's asking instance");
            }
        }

        return $this->activity_logs_collection;
    }
}
