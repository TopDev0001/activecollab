<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate estimates to tasks model.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateEstimatesToTasksModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        $tasks->addColumn(DBIntegerColumn::create('job_type_id', 5, 0)->setUnsigned(true), 'due_on');
        $tasks->addColumn(DBDecimalColumn::create('estimate', 12, 2, 0)->setUnsigned(true), 'job_type_id');
        $tasks->addColumn(
            new DBActionOnByColumn('estimated'),
            'estimate'
        );

        if ($this->tableExists('estimates')) {
            $estimates = $this->useTables('estimates')[0];

            $has_current_estimate = [];
            $modifications = [];

            if ($rows = $this->execute("SELECT parent_id, job_type_id, value, created_on, created_by_id, created_by_name, created_by_email FROM $estimates WHERE parent_type = 'Task' ORDER BY created_on DESC")) {
                foreach ($rows as $row) {
                    if (!in_array($row['parent_id'], $has_current_estimate)) {
                        $this->execute('UPDATE ' . $tasks->getName() . ' SET job_type_id = ?, estimate = ?, estimated_on = ?, estimated_by_id = ?, estimated_by_name = ?, estimated_by_email = ? WHERE id = ?', $row['job_type_id'], $row['value'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['parent_id']);
                        $has_current_estimate[] = $row['parent_id'];
                    }
                }
            }

            $this->dropTable('estimates');
        }

        $this->doneUsingTables();
    }
}
