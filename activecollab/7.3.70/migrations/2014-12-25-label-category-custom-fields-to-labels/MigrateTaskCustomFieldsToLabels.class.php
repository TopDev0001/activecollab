<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate task custom fields to labels.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskCustomFieldsToLabels extends AngieModelMigration
{
    /**
     * @var array
     */
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
        [$labels, $parents_labels] = $this->useTables('labels', 'parents_labels');

        $custom_fields = $this->getEnabledCustomFields();

        if (count($custom_fields)) {
            $custom_field_conditions = [];

            foreach (array_keys($custom_fields) as $field_name) {
                $custom_field_conditions[] = "($field_name IS NOT NULL AND $field_name != '')";
            }

            if ($task_rows = $this->execute('SELECT id, ' . implode(', ', array_keys($custom_fields)) . ' FROM ' . $tasks->getName() . ' WHERE ' . implode(' OR ', $custom_field_conditions))) {
                $batch = new DBBatchInsert($parents_labels, ['parent_type', 'parent_id', 'label_id'], 500, DBBatchInsert::REPLACE_RECORDS);

                foreach ($task_rows as $task_row) {
                    foreach ($custom_fields as $field_name => $field_label) {
                        if ($task_row[$field_name]) {
                            $batch->insertEscapedArray(["'Task'", DB::escape($task_row['id']), DB::escape($this->getLabelIdFromName(substr_utf($field_label . ': ' . trim($task_row[$field_name]), 0, 50), $labels))]);
                        }
                    }
                }

                $batch->done();
            }
        }

        $tasks->dropColumn('custom_field_1');
        $tasks->dropColumn('custom_field_2');
        $tasks->dropColumn('custom_field_3');

        $this->doneUsingTables();
    }

    /**
     * Return a list of enabled custom fields for tasks.
     *
     * @return array
     */
    private function getEnabledCustomFields()
    {
        $custom_fields_table = $this->useTables('custom_fields')[0];
        $custom_fields = [];

        if ($rows = $this->execute("SELECT field_name, label FROM $custom_fields_table WHERE parent_type = 'Task' AND is_enabled = ? ORDER BY field_name", true)) {
            foreach ($rows as $row) {
                $custom_fields[$row['field_name']] = $row['label'] ? $row['label'] : 'Unknown';
            }

            $this->execute("DELETE FROM $custom_fields_table WHERE parent_type = 'Task'");
        }

        return $custom_fields;
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
