<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\Metric\IsUsingTrackingFlag;
use ActiveCollab\Module\Tracking\Metric\TimeRecordsCollection;

/**
 * Handle on_extra_stats event.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage handlers
 */

/**
 * @param array     $stats
 * @param DateValue $date
 */
function tracking_handle_on_extra_stats(array &$stats, $date)
{
    (new IsUsingTrackingFlag())->getValueFor($date)->addTo($stats);
    (new TimeRecordsCollection())->getValueFor($date)->addTo($stats);
}
