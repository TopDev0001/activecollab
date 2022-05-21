<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate add archive state to expense categories.
 *
 * @package ActiveCollab.migrations
 */
class MigrateAddArchiveStateToExpenseCategories extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $expense_categories = $this->useTableForAlter('expense_categories');

        $expense_categories->addColumn(new DBArchiveColumn(), 'is_default');

        $this->doneUsingTables();
    }
}
