<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DayOff extends FwDayOff
{
    public function save()
    {
        // clear cache for availabilities when day off added or dates are changed
        if ($this->isNew() || $this->isModifiedField('start_on') || $this->isModifiedField('end_on')) {
            AvailabilityRecords::clearCache();
        }

        return parent::save();
    }

    public function delete($bulk = false)
    {
        parent::delete($bulk);
        AvailabilityRecords::clearCache();
    }
}
