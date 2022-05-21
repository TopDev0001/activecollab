<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Services;

use Project;
use TimeRecord;
use UserInternalRate;

interface TrackingServiceInterface
{
    public function setInternalRateForUserTimeRecords(UserInternalRate $user_internal_rate, string $event_type);

    public function updateAllTimeRecordsForUser(int $user_id, float $internal_rate);

    public function updateTimeRecordsForUserForRange(int $user_id, float $internal_rate, string $next_internal_rate_valid_from, string $internal_rate_valid_from);

    public function updateRecordsUntilNextRate(int $user_id, float $internal_rate, string $internal_rate_valid_from, string $next_internal_rate_valid_from);

    public function updateRecordsFromInternalRatesValidFromOn(int $user_id, float $internal_rate, string $internal_rate_valid_from);

    public function calcRatesForProjectTimeRecords(Project $project);

    public function calcRatesForTimeRecordsIds(array $time_records_ids);

    public function getInternalRateForTimeRecord(TimeRecord $time_record);

    public function getJobTypeRateForTimeRecord(TimeRecord $time_record): float;
}
