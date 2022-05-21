<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate task categories to labels.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskCategoriesToLabels extends AngieModelMigration
{
    private $label_names = false;

    /**
     * Execute only after label name field has been extended.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateLabelNameLength');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');
        [$categories, $labels, $parents_labels] = $this->useTables('categories', 'labels', 'parents_labels');

        if ($category_rows = $this->execute("SELECT id, name FROM $categories WHERE type IN ('GlobalTaskCategory', 'LocalTaskCategory')")) {
            $batch = new DBBatchInsert($parents_labels, ['parent_type', 'parent_id', 'label_id'], 500, DBBatchInsert::REPLACE_RECORDS);

            foreach ($category_rows as $category) {
                if ($task_ids = $this->executeFirstColumn('SELECT id FROM ' . $tasks->getName() . ' WHERE category_id = ?', $category['id'])) {
                    $label_id = DB::escape($this->getLabelIdFromName(substr_utf($category['name'], 0, 50), $labels));

                    foreach ($task_ids as $task_id) {
                        $batch->insertEscapedArray(["'Task'", DB::escape($task_id), $label_id]);
                    }
                }
            }

            $batch->done();

            $this->execute("DELETE FROM $categories WHERE type IN ('GlobalTaskCategory', 'LocalTaskCategory')");
        }

        $tasks->dropColumn('category_id');

        $this->doneUsingTables();
    }

    /**
     * Get label ID from label name.
     *
     * @param  string $label_name
     * @param  string $labels_table
     * @return int
     */
    private function getLabelIdFromName($label_name, $labels_table)
    {
        if ($this->label_names === false) {
            $this->label_names = [];

            if ($rows = DB::execute("SELECT id, LOWER(name) AS 'name' FROM $labels_table WHERE type = 'TaskLabel'")) {
                foreach ($rows as $row) {
                    $this->label_names[$row['name']] = $row['id'];
                }
            }
        }

        $label_id_key = strtolower_utf($label_name);

        if (empty($this->label_names[$label_id_key])) {
            // MySQL does not use the same way to compare string as PHP does, so we'll try to find it first (task #1506)
            if ($label_id = $this->executeFirstCell("SELECT id FROM $labels_table WHERE type = 'TaskLabel' AND name = ?", $label_name)) {
                $this->label_names[$label_id_key] = $label_id;
            } else {
                $this->execute("INSERT INTO $labels_table (type, name, updated_on) VALUES ('TaskLabel', ?, UTC_TIMESTAMP())", $label_name);

                $this->label_names[$label_id_key] = $this->lastInsertId();
            }
        }

        return $this->label_names[$label_id_key];
    }
}
