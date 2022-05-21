<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update drop state from comments model.
 *
 * @package angie.migrations
 */
class MigrateDropStateFromCommentsModel extends AngieModelMigration
{
    /**
     * Migrate permanently deleted comments.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateDropTypeFromCommentsModel');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $comments = $this->useTableForAlter('comments');

        $comments->addColumn(
            new DBBoolColumn('is_trashed'),
            'updated_by_email'
        );
        $comments->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $comments->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $comments->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $comments->addIndex(DBIndex::create('trashed_by_id'));

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $comments->getName() . ' SET is_trashed = ?, original_is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, false, STATE_TRASHED);

        $comments->dropColumn('state');
        $comments->dropColumn('original_state');

        $this->doneUsingTables();
    }
}
