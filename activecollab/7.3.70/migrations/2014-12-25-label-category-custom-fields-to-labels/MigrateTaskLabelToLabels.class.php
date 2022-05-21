<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate task label to labels.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskLabelToLabels extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');
        [$labels, $parents_labels] = $this->useTables('labels', 'parents_labels');

        if ($label_ids = $this->executeFirstColumn("SELECT id FROM $labels WHERE type = 'TaskLabel'")) {
            if ($rows = $this->execute('SELECT id, label_id FROM ' . $tasks->getName() . ' WHERE label_id IN (?)', $label_ids)) {
                $batch = new DBBatchInsert($parents_labels, ['parent_type', 'parent_id', 'label_id'], 500, DBBatchInsert::REPLACE_RECORDS);

                foreach ($rows as $row) {
                    $batch->insertEscapedArray(["'Task'", DB::escape($row['id']), DB::escape($row['label_id'])]);
                }

                $batch->done();
            }
        }

        $tasks->dropColumn('label_id');

        $this->doneUsingTables();
    }
}
