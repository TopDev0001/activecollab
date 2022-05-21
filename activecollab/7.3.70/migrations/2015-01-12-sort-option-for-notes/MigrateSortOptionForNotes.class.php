<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add sorting option for notes.
 *
 * @package ActiveCollab.migrations
 */
class MigrateSortOptionForNotes extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('sort_mode_project_notes', 'recently_updated');
    }
}
