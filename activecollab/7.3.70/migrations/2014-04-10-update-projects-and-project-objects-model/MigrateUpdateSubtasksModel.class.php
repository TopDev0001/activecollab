<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate subtasks model.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateUpdateSubtasksModel extends AngieModelMigration
{
    /**
     * Construct.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateTasksToNewStorage');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $subtasks = $this->useTableForAlter('subtasks');

        $this->execute('DELETE FROM ' . $subtasks->getName() . ' WHERE parent_type != ?', 'Task');

        foreach (['type', 'parent', 'parent_type', 'parent_id', 'label_id', 'priority'] as $index_to_drop) {
            if ($subtasks->indexExists($index_to_drop)) {
                $subtasks->dropIndex($index_to_drop);
            }
        }

        $subtasks->dropColumn('type');
        $subtasks->alterColumn('parent_id', DBIntegerColumn::create('task_id', 10, 0)->setUnsigned(true));
        $subtasks->dropColumn('parent_type');
        $subtasks->dropColumn('label_id');
        $subtasks->dropColumn('priority');

        if ($subtasks->getColumn('visibility') instanceof DBColumn) {
            $subtasks->dropColumn('visibility');
        }

        if ($subtasks->getColumn('original_visibility') instanceof DBColumn) {
            $subtasks->dropColumn('original_visibility');
        }

        $subtasks->addColumn(
            new DBBoolColumn('is_trashed'),
            'position'
        );
        $subtasks->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $subtasks->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $subtasks->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $subtasks->addIndex(DBIndex::create('trashed_by_id'));

        // ---------------------------------------------------
        //  State
        // ---------------------------------------------------

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $subtasks->getName() . ' SET is_trashed = ?, original_is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, false, STATE_TRASHED);

        $subtasks->dropColumn('state');
        $subtasks->dropColumn('original_state');

        $subtasks->addIndex(new DBIndex('task_id'));

        // ---------------------------------------------------
        //  Relations
        // ---------------------------------------------------

        $this->execute('UPDATE ' . $this->useTables('subscriptions')[0] . " SET parent_type = 'Subtask' WHERE parent_type = 'ProjectObjectSubtask'");

        $this->doneUsingTables();
    }
}
