<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop unsuported data filters.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropUnsupportedSavedDataFilters extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('DELETE FROM ' . $this->useTables('data_filters')[0] . ' WHERE type NOT IN (?)', ['AssignmentFilter', 'InvoicePaymentsFilter', 'InvoicesFilter', 'ProjectsFilter', 'TrackingReport', 'SummarizedTrackingReport']);
        $this->doneUsingTables();
    }
}
