<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate tasks to new storage.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateTasksToNewStorage extends AngieModelMigration
{
    /**
     * Construct migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateDiscussionsToNewStorage');
    }

    /**
     * Migrate tasks to new models.
     */
    public function up()
    {
        $project_objects = $this->useTableForAlter('project_objects');

        $this->execute('DELETE FROM ' . $project_objects->getName() . ' WHERE type != ?', 'Task'); // Clear project objects table

        foreach (['type', 'module', 'parent', 'parent_type', 'parent_id', 'integer_field_1', 'task_id'] as $index_to_drop) {
            if ($project_objects->indexExists($index_to_drop)) {
                $project_objects->dropIndex($index_to_drop);
            }
        }

        $project_objects->alterColumn('integer_field_1', DBIntegerColumn::create('task_id', 10, 0)->setUnsigned(true), 'project_id');

        $project_objects->dropColumn('type');
        $project_objects->dropColumn('source');
        $project_objects->dropColumn('module');
        $project_objects->dropColumn('version');

        $project_objects->dropColumn('varchar_field_1');
        $project_objects->dropColumn('varchar_field_2');
        $project_objects->dropColumn('varchar_field_3');

        $project_objects->dropColumn('integer_field_2');
        $project_objects->dropColumn('integer_field_3');

        $project_objects->dropColumn('float_field_1');
        $project_objects->dropColumn('float_field_2');
        $project_objects->dropColumn('float_field_3');

        $project_objects->dropColumn('text_field_1');
        $project_objects->dropColumn('text_field_2');
        $project_objects->dropColumn('text_field_3');

        $project_objects->dropColumn('date_field_1');
        $project_objects->dropColumn('date_field_2');
        $project_objects->dropColumn('date_field_3');

        $project_objects->dropColumn('datetime_field_1');
        $project_objects->dropColumn('datetime_field_2');
        $project_objects->dropColumn('datetime_field_3');

        $project_objects->dropColumn('boolean_field_1');
        $project_objects->dropColumn('boolean_field_2');
        $project_objects->dropColumn('boolean_field_3');

        if ($project_objects->getColumn('parent_type') instanceof DBColumn) {
            $project_objects->dropColumn('parent_type');
        }

        if ($project_objects->getColumn('parent_id') instanceof DBColumn) {
            $project_objects->dropColumn('parent_id');
        }

        // ---------------------------------------------------
        //  Is hidden from clients
        // ---------------------------------------------------

        $project_objects->addColumn(
            new DBBoolColumn('is_hidden_from_clients'),
            'position'
        );

        defined('VISIBILITY_PRIVATE') or define('VISIBILITY_PRIVATE', 0);

        $this->execute('UPDATE ' . $project_objects->getName() . ' SET is_hidden_from_clients = ? WHERE visibility = ?', true, VISIBILITY_PRIVATE);

        $project_objects->dropColumn('visibility');
        $project_objects->dropColumn('original_visibility');

        // ---------------------------------------------------
        //  State
        // ---------------------------------------------------

        $project_objects->addColumn(
            new DBBoolColumn('is_trashed'),
            'is_hidden_from_clients'
        );
        $project_objects->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $project_objects->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $project_objects->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $project_objects->addIndex(DBIndex::create('trashed_by_id'));

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $project_objects->getName() . ' SET is_trashed = ?, original_is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, false, STATE_TRASHED);

        $project_objects->dropColumn('state');
        $project_objects->dropColumn('original_state');

        // ---------------------------------------------------
        //  Fix project_id and task_id before adding the key
        // ---------------------------------------------------

        if ($rows = $this->execute('SELECT COUNT(id) AS row_count, project_id, task_id FROM ' . $project_objects->getName() . ' GROUP BY project_id, task_id HAVING row_count > 1')) {
            foreach ($rows as $row) {
                do {
                    $id_to_update = $this->executeFirstCell('SELECT id FROM ' . $project_objects->getName() . ' WHERE project_id = ? AND task_id = ? ORDER BY created_on DESC LIMIT 0, 1', $row['project_id'], $row['task_id']);
                    $next_task_id = $this->executeFirstCell('SELECT MAX(task_id) FROM ' . $project_objects->getName() . ' WHERE project_id = ?', $row['project_id']) + 1;

                    $this->execute('UPDATE ' . $project_objects->getName() . ' SET task_id = ? WHERE id = ?', $next_task_id, $id_to_update);
                } while ($this->executeFirstCell('SELECT COUNT(id) AS row_count FROM ' . $project_objects->getName() . ' WHERE project_id = ? AND task_id = ?', $row['project_id'], $row['task_id']) > 1);
            }
        }

        $project_objects->addIndex(DBIndex::create('project_task_id', DBIndex::UNIQUE, ['project_id', 'task_id']));

        $this->doneUsingTables();

        $this->renameTable('project_objects', 'tasks');
    }
}
