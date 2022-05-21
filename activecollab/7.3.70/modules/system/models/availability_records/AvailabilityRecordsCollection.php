<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class AvailabilityRecordsCollection extends ModelCollection
{
    private $tag = false;

    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $hash = sha1(
                $this->getTimestampHash('updated_on') . '-' .
                DB::executeFirstCell("SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM day_offs ORDER BY id") . '-' .
                DB::executeFirstCell('SELECT updated_on FROM config_options WHERE name = ?', 'time_workdays') . '-' .
                DB::executeFirstCell('SELECT updated_on FROM config_options WHERE name = ?', 'time_first_week_day')
            );

            $this->tag = $this->prepareTagFromBits($user->getEmail(), $hash);
        }

        return $this->tag;
    }
}
