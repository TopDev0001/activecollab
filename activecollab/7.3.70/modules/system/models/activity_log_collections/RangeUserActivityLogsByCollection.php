<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class RangeUserActivityLogsByCollection extends UserActivityLogsCollection
{
    private string $dates;

    public function &setDates(string $dates): self
    {
        $this->dates = $dates;

        return $this;
    }

    private ModelCollection $activity_logs_collection;

    protected function &getActivityLogsCollection(): ModelCollection
    {
        if (empty($this->activity_logs_collection)) {
            if ($this->checkCollectionNameValues()) {
                $this->activity_logs_collection = ActivityLogs::prepareCollection(
                    sprintf(
                        'range_activity_logs_by_%s_%s_page_%s',
                        $this->getForOrBy()->getId(),
                        $this->dates,
                        $this->getCurrentPage()
                    ),
                    $this->getWhosAsking()
                );
            } else {
                throw new ImpossibleCollectionError("Invalid user and/or who's asking instance");
            }
        }

        return $this->activity_logs_collection;
    }

    private function checkCollectionNameValues(): bool
    {
        return strpos($this->dates, ':') !== false
            && $this->getForOrBy() instanceof User
            && $this->getWhosAsking() instanceof User;
    }
}
