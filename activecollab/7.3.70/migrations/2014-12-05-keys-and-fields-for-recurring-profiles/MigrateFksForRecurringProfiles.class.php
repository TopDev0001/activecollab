<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate foreign keys for recurring profiles.
 *
 * @package ActiveCollab.migrations
 */
class MigrateFksForRecurringProfiles extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $recurring_profiles = $this->useTableForAlter('recurring_profiles');

        foreach (['company_id', 'currency_id', 'language_id', 'project_id'] as $field) {
            $this->execute('UPDATE ' . $recurring_profiles->getName() . " SET $field = '0' WHERE $field IS NULL");

            $recurring_profiles->alterColumn($field, DBFkColumn::create($field));
        }

        $this->doneUsingTables();
    }
}
