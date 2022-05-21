<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated_on field to categories model.
 *
 * @package angie.migrations
 */
class MigrateUpdatedOnForCategoriesModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $categories = $this->useTableForAlter('categories');

        $categories->addColumn(new DBUpdatedOnColumn(), 'created_by_email');
        $this->execute('UPDATE ' . $categories->getName() . ' SET updated_on = created_on');

        $this->doneUsingTables();
    }
}
