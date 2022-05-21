<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddRecurringTasksTable extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if (!$this->tableExists('recurring_tasks')) {
            $this->createTable(
                DB::createTable('recurring_tasks')->addColumns(
                    [
                        new DBIdColumn(),
                        DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                        DBFkColumn::create('task_list_id', 0, true),
                        DBIntegerColumn::create('assignee_id', 10, 0)->setUnsigned(true),
                        DBIntegerColumn::create('delegated_by_id', 10, 0)->setUnsigned(true),
                        new DBNameColumn(150),
                        (new DBTextColumn('body'))->setSize(DBTextColumn::BIG),
                        new DBBoolColumn('is_important'),
                        new DBCreatedOnByColumn(true, true),
                        new DBUpdatedOnByColumn(),
                        DBIntegerColumn::create('start_in', 10, null)->setUnsigned(true),
                        DBIntegerColumn::create('due_in', 10, null)->setUnsigned(true),
                        DBIntegerColumn::create('job_type_id', 5, 0)->setUnsigned(true),
                        DBDecimalColumn::create('estimate', 12, 2, 0)->setUnsigned(true),
                        DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
                        new DBBoolColumn('is_hidden_from_clients'),
                        new DBTrashColumn(true),
                        new DBEnumColumn('repeat_frequency', ['never', 'daily', 'weekly', 'monthly'], 'never'),
                        DBIntegerColumn::create('repeat_amount', 10, 0)->setUnsigned(true),
                        DBIntegerColumn::create('triggered_number', 10, 0)->setUnsigned(true),
                        new DBDateColumn('last_trigger_on'),
                        new DBAdditionalPropertiesColumn(),
                    ]
                )->addIndices(
                    [
                        DBIndex::create('project_id'),
                        DBIndex::create('assignee_id'),
                        DBIndex::create('delegated_by_id'),
                    ]
                )
            );
        }

        $this->useTableForAlter('tasks')->addColumn(DBFkColumn::create('created_from_recurring_task_id'));
    }
}
