<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate projects table foreign keys.
 *
 * @package ActiveCollab.migrations
 */
class MigrateProjectModelFks extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        foreach (['template_id', 'based_on_id', 'company_id', 'category_id', 'label_id', 'currency_id', 'leader_id'] as $column) {
            if ($projects->indexExists($column)) {
                $projects->dropIndex($column);
            }

            $this->execute('UPDATE ' . $projects->getName() . ' SET ' . $column . ' = ? WHERE ' . $column . ' IS NULL', 0);

            $projects->alterColumn($column, DBFkColumn::create($column));
        }

        foreach (['category_id', 'company_id', 'label_id', 'leader_id'] as $column) {
            $projects->addIndex(DBIndex::create($column));
        }

        $this->doneUsingTables();
    }
}
