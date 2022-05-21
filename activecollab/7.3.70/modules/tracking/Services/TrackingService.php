<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Services;

use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\UserInternalRateEvents\UserInternalRatePreDeletedEventInterface;
use DateTimeValue;
use DB;
use Project;
use TimeRecord;
use UserInternalRate;
use UserInternalRates;

class TrackingService implements TrackingServiceInterface
{
    private $current_date;

    public function __construct(
        DateTimeValue $current_date
    )
    {
        $this->current_date = $current_date;
    }

    public function setInternalRateForUserTimeRecords(UserInternalRate $user_internal_rate, string $event_type)
    {
        $user_id = $user_internal_rate->getUserId();
        $rate = $user_internal_rate->getRate();
        $internal_rate_valid_from = $user_internal_rate->getValidFrom()->toMySQL();

        /** @var UserInternalRate | null $previous_internal_rate */
        $previous_internal_rate = UserInternalRates::findOneBySql('SELECT * FROM user_internal_rates WHERE valid_from < ? AND user_id = ? ORDER BY valid_from DESC LIMIT 1 ', $internal_rate_valid_from, $user_id);
        /** @var UserInternalRate | null $next_internal_rate */
        $next_internal_rate = UserInternalRates::findOneBySql('SELECT * FROM user_internal_rates WHERE valid_from > ? ORDER BY valid_from ASC LIMIT 1', $internal_rate_valid_from, $user_id);

        if ($next_internal_rate) {
            $next_internal_rate_valid_from = $next_internal_rate->getValidFrom()->toMySQL();
        }

        if (!$previous_internal_rate && !$next_internal_rate) {
            if ($event_type === UserInternalRatePreDeletedEventInterface::NAME) {
                $rate = 0;
            }

            return $this->updateAllTimeRecordsForUser($user_id, $rate);
        } elseif (!$previous_internal_rate && $next_internal_rate) {
            if ($event_type === UserInternalRatePreDeletedEventInterface::NAME) {
                $rate = $next_internal_rate->getRate();
            }

            return $this->updateTimeRecordsForUserForRange($user_id, $rate, $next_internal_rate_valid_from, $internal_rate_valid_from);
        } elseif ($previous_internal_rate && $next_internal_rate) {
            if ($event_type === UserInternalRatePreDeletedEventInterface::NAME) {
                $rate = $previous_internal_rate->getRate();
            }

            return $this->updateRecordsUntilNextRate($user_id, $rate, $internal_rate_valid_from, $next_internal_rate_valid_from);
        } else {
            if ($event_type === UserInternalRatePreDeletedEventInterface::NAME) {
                $rate = $previous_internal_rate->getRate();
            }

            return $this->updateRecordsFromInternalRatesValidFromOn($user_id, $rate, $internal_rate_valid_from);
        }
    }

    public function updateAllTimeRecordsForUser(int $user_id, float $internal_rate)
    {
        return DB::execute('UPDATE time_records SET internal_rate = ?, updated_on = ? WHERE user_id = ?', $internal_rate, $this->current_date, $user_id);
    }

    public function updateTimeRecordsForUserForRange(
        int $user_id,
        float $internal_rate,
        string $next_internal_rate_valid_from,
        string $internal_rate_valid_from
    )
    {
       return DB::execute('UPDATE time_records SET internal_rate = ?, updated_on = ? WHERE user_id = ? AND record_date < ?', $internal_rate, $this->current_date, $user_id, $next_internal_rate_valid_from);
    }

    public function updateRecordsUntilNextRate(
        int $user_id,
        float $internal_rate,
        string $internal_rate_valid_from,
        string $next_internal_rate_valid_from
    )
    {
        return DB::execute('UPDATE time_records SET internal_rate = ?, updated_on = ? WHERE user_id = ? AND record_date >= ? AND record_date < ?', $internal_rate, $this->current_date, $user_id, $internal_rate_valid_from, $next_internal_rate_valid_from);
    }

    public function updateRecordsFromInternalRatesValidFromOn(
        int $user_id,
        float $internal_rate,
        string $internal_rate_valid_from
    )
    {
        return DB::execute('UPDATE time_records SET internal_rate = ?, updated_on = ? WHERE user_id = ? AND record_date >= ?', $internal_rate, $this->current_date, $user_id, $internal_rate_valid_from);
    }

    public function calcRatesForProjectTimeRecords(Project $project)
    {
        DB::execute(
            "UPDATE time_records AS tr
             LEFT JOIN projects AS pr ON pr.id = tr.parent_id
             LEFT JOIN job_types AS jt ON tr.job_type_id = jt.id
             LEFT JOIN custom_hourly_rates AS chr1 ON tr.job_type_id = chr1.job_type_id AND chr1.parent_type = 'Company' AND chr1.parent_id = pr.id
             LEFT join custom_hourly_rates AS chr2 ON tr.job_type_id = chr2.job_type_id AND chr2.parent_type = 'Project' AND chr2.parent_id = pr.id
            SET tr.job_type_hourly_rate = COALESCE(chr2.hourly_rate, chr1.hourly_rate, jt.default_hourly_rate, 0), tr.updated_on = UTC_TIMESTAMP()
            WHERE tr.parent_type = 'Project' AND pr.id = ?
            ",
            $project->getId()
        );

        DB::execute(
            "UPDATE time_records AS tr
             LEFT JOIN tasks AS t ON t.id = tr.parent_id
             LEFT JOIN job_types AS jt ON tr.job_type_id = jt.id
             LEFT JOIN custom_hourly_rates AS chr1 ON tr.job_type_id = chr1.job_type_id AND chr1.parent_type = 'Company' AND chr1.parent_id = t.project_id
             LEFT join custom_hourly_rates AS chr2 ON tr.job_type_id = chr2.job_type_id AND chr2.parent_type = 'Project' AND chr2.parent_id = t.project_id
            SET tr.job_type_hourly_rate = COALESCE(chr2.hourly_rate, chr1.hourly_rate, jt.default_hourly_rate, 0), tr.updated_on = UTC_TIMESTAMP()
            WHERE tr.parent_type = 'Task' AND t.project_id = ?
            ",
            $project->getId()
        );
    }

    public function calcRatesForTimeRecordsIds(array $timeRecordIds)
    {
        $query = "
            UPDATE time_records AS tr
             LEFT JOIN tasks AS t ON tr.parent_type = 'Task' AND tr.parent_id = t.id
             LEFT JOIN projects AS pr ON IF (tr.parent_type = 'Project', pr.id = tr.parent_id, pr.id = t.project_id)
             LEFT JOIN job_types AS jt ON tr.job_type_id = jt.id
             LEFT JOIN custom_hourly_rates AS chr1 ON tr.job_type_id = chr1.job_type_id AND chr1.parent_type = 'Company' AND chr1.parent_id = pr.company_id
             LEFT join custom_hourly_rates AS chr2 ON tr.job_type_id = chr2.job_type_id AND chr2.parent_type = 'Project' AND chr2.parent_id = pr.id
            SET tr.job_type_hourly_rate = COALESCE(chr2.hourly_rate, chr1.hourly_rate, jt.default_hourly_rate, 0), tr.updated_on = UTC_TIMESTAMP()
            WHERE tr.id IN (?)
        ";

        DB::execute($query, $timeRecordIds);
    }

    public function getInternalRateForTimeRecord(TimeRecord $time_record): float
    {
        $internalRate = UserInternalRates::getByDate($time_record->getUserId(), $time_record->getRecordDate());

        return $internalRate ? $internalRate->getRate() : 0;
    }

    public function getJobTypeRateForTimeRecord(TimeRecord $time_record): float
    {
        $jobType = $time_record->getJobType();
        $jobTypeHourlyRate = $jobType->getHourlyRateFor($time_record->getProject());

        return $jobTypeHourlyRate ? $jobTypeHourlyRate : 0;
    }
}
