<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migreate tracking reports for feather.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigrateTrackingReportsForFeather extends AngieModelMigration
{
    /**
     * Migreate up.
     */
    public function up()
    {
        $data_filters = $this->useTables('data_filters')[0];

        if ($rows = $this->execute("SELECT id, raw_additional_properties FROM $data_filters WHERE type = 'TrackingReport'")) {
            foreach ($rows as $row) {
                $attributes = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : [];

                if (array_key_exists('sum_by_user', $attributes)) {
                    $type = $attributes['sum_by_user'] ? 'SummarizedTrackingReport' : 'TrackingReport';
                    unset($attributes['sum_by_user']);

                    $this->execute("UPDATE $data_filters SET type = ?, raw_additional_properties = ? WHERE id = ?", $type, serialize($attributes), $row['id']);
                }
            }
        }

        $this->doneUsingTables();
    }
}
