<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsBillableAndMembersCanChangeBillableFields extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('projects')) {
            $projects = $this->useTableForAlter('projects');

            if (!$projects->getColumn('is_billable')) {
                $projects->addColumn(
                    new DBBoolColumn('is_billable', true),
                    'is_tracking_enabled'
                );
            }
            if (!$projects->getColumn('members_can_change_billable')) {
                $projects->addColumn(
                    new DBBoolColumn('members_can_change_billable', true),
                    'is_tracking_enabled'
                );
            }
        }

        if ($this->tableExists('tasks')) {
            $tasks = $this->useTableForAlter('tasks');

            if (!$tasks->getColumn('is_billable')) {
                $tasks->addColumn(
                    new DBBoolColumn('is_billable', true),
                    'is_hidden_from_clients'
                );
            }
        }

        $this->doneUsingTables();
    }
}
