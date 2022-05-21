<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add stored_card_id field to recurring profile.
 *
 * @package ActiveCollab.migrations
 */
class MigrateStoredCardsForRecurringProfiles extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->useTableForAlter('recurring_profiles')->addColumn(DBFkColumn::create('stored_card_id'));
        $this->doneUsingTables();
    }
}
