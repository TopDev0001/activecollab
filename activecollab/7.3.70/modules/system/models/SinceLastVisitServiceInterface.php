<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface SinceLastVisitServiceInterface
{
    const LAST_VISIT_DELAY = 5; // seconds
    /**
     * Get timestamp when user visited object last time.
     *
     * @param  int|null $delay
     * @return int|null
     */
    public function getLastVisitTimestamp(DataObject $object, $delay = null);
}
