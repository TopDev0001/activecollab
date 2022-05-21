<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\Services\TrackingServiceInterface;

/**
 * Hourly rates implementation.
 *
 * @package activeCollab.modules.system
 * @subpackage models
 */
trait IHourlyRatesImplementation
{
    /**
     * Return hourly rates.
     *
     * @return array
     */
    public function getHourlyRates()
    {
        return JobTypes::getIdRateMapFor($this);
    }

    private function hasChanged(array $old_values, array $new_values): bool
    {
        if (count($old_values) !== count($new_values)) {
            return true;
        }
        foreach ($old_values as $job_type_id => $rate) {
            if (array_key_exists($job_type_id, $new_values) && (float) $new_values[$job_type_id] !== (float) $rate) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set custom hourly rates.
     *
     * @param  array     $hourly_rates
     * @throws Exception
     */
    public function setHourlyRates($hourly_rates)
    {
        $old_values = DB::executeIdNameMap('SELECT job_type_id AS id, hourly_rate AS name FROM custom_hourly_rates WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId());
        if (!$this->hasChanged($old_values, $hourly_rates)) {
            return;
        }

        try {
            DB::beginWork('Begin: set hourly rates @ ' . __CLASS__);

            $parent_type = DB::escape(get_class($this));
            $parent_id = DB::escape($this->getId());

            DB::execute("DELETE FROM custom_hourly_rates WHERE parent_type = $parent_type AND parent_id = $parent_id");

            if ($hourly_rates && is_foreachable($hourly_rates)) {
                $batch = new DBBatchInsert('custom_hourly_rates', ['parent_type', 'parent_id', 'job_type_id', 'hourly_rate', 'updated_on']);

                foreach ($hourly_rates as $job_type_id => $hourly_rate) {
                    if (empty($job_type_id)) {
                        continue;
                    }

                    $batch->insertEscapedArray([$parent_type, $parent_id, DB::escape($job_type_id), DB::escape(floatval($hourly_rate)), DB::escape(DateTimeValue::now()->toMySQL())]);
                }

                $batch->done();
            }

            // Touch projects to invalidate caches
            if ($this instanceof Company && $projects = $this->getActiveProjects()) {
                foreach ($projects as $project) {
                    $project->touchDoesntUpdateActivity();
                    $project->touch();
                    $project->touchUpdatesActivity();
                }
            }

            if ($this instanceof Project) {
                AngieApplication::getContainer()->get(TrackingServiceInterface::class)->calcRatesForProjectTimeRecords($this);
            }

            AngieApplication::cache()->removeByObject($this);

            DB::commit('Done: set hourly rates @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: set hourly rates @ ' . __CLASS__);
            throw $e;
        }
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return parent ID.
     *
     * @return int
     */
    abstract public function getId();
}
