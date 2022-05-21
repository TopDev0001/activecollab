<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove next trigger on field from recurring profile table.
 *
 * @package activeCollab.modules.system
 */
class MigrateRemoveNextTriggerOn extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $recurring_profiles = $this->useTableForAlter('recurring_profiles');

        $recurring_profiles->dropIndex('next_trigger_on');
        $recurring_profiles->dropColumn('next_trigger_on');

        $this->doneUsingTables();
    }
}
