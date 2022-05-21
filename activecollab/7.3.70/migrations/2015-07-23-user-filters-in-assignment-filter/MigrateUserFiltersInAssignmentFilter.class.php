<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate user filter values in assignments filter.
 *
 * @package ActiveCollab.migrations
 */
class MigrateUserFiltersInAssignmentFilter extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $data_filters = $this->useTables('data_filters')[0];

        if ($filters = $this->execute("SELECT id, raw_additional_properties FROM $data_filters WHERE type = 'AssignmentFilter'")) {
            foreach ($filters as $filter) {
                $properties = $filter['raw_additional_properties'] ? unserialize($filter['raw_additional_properties']) : [];
                $properties_changed = false;

                foreach (['created_by_company_id' => 'created_by_company_member_id', 'delegated_by_company_id' => 'delegated_by_company_member_id', 'completed_by_company_id' => 'completed_by_company_member_id'] as $old_key => $new_key) {
                    if (array_key_exists($old_key, $properties)) {
                        $properties[$new_key] = $properties[$old_key];
                        unset($properties[$old_key]);

                        $properties_changed = true;
                    }
                }

                if ($properties_changed) {
                    $this->execute("UPDATE $data_filters SET raw_additional_properties = ? WHERE id = ?", serialize($properties), $filter['id']);
                }
            }
        }

        $this->doneUsingTables();
    }
}
