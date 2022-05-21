<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated on field to estimates model.
 *
 * @package ActiveCollab.migrations
 */
class MigrateUpdatedOnForEstimates extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $estimates = $this->useTableForAlter('estimates');
        $estimates->addColumn(new DBUpdatedOnColumn(), 'created_by_email');

        $this->execute('UPDATE ' . $estimates->getName() . ' SET updated_on = sent_on WHERE sent_on IS NOT NULL');
        $this->execute('UPDATE ' . $estimates->getName() . ' SET updated_on = created_on WHERE updated_on IS NULL');

        $estimates->addIndex(DBIndex::create('updated_on'));

        $this->doneUsingTables();
    }
}
