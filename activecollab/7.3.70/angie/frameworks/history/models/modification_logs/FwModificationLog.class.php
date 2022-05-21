<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Framework level modification log entry implementation.
 *
 * @package angie.frameworks.history
 * @subpackage models
 */
abstract class FwModificationLog extends BaseModificationLog
{
    /**
     * Return modified fiels and values.
     *
     * @return array
     */
    public function getModifiedFieldValues()
    {
        $result = [];

        if ($rows = DB::execute('SELECT field, old_value, new_value FROM modification_log_values WHERE modification_id = ?', $this->getId())) {
            foreach ($rows as $row) {
                $result[$row['field']] = [$row['old_value'], $row['new_value']];
            }
        }

        return $result;
    }
}
