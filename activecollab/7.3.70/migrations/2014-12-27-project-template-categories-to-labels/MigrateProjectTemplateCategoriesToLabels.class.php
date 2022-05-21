<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate project template task categories to labels.
 *
 * @package ActiveCollab.migrations
 * @subpackage
 */
class MigrateProjectTemplateCategoriesToLabels extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$project_template_elements, $labels, $parents_labels] = $this->useTables('project_template_elements', 'labels', 'parents_labels');

        if ($rows = $this->execute("SELECT id, raw_additional_properties FROM $project_template_elements WHERE type = 'ProjectTemplateTask'")) {
            foreach ($rows as $row) {
                $properties = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : [];

                if (array_key_exists('category_name', $properties)) {
                    if ($category_name = trim($properties['category_name'])) {
                        $this->execute("INSERT INTO $parents_labels (parent_type, parent_id, label_id) VALUES ('ProjectTemplateTask', ?, ?)", $row['id'], $this->getLabelIdFromName($category_name, $labels));
                    }

                    unset($properties['category_name']);

                    $this->execute("UPDATE $project_template_elements SET raw_additional_properties = ? WHERE id = ?", serialize($properties), $row['id']);
                }
            }
        }
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
        if ($id = DB::executeFirstCell("SELECT id FROM $labels_table WHERE type = ? AND name = ?", 'TaskLabel', $label_name)) {
            return $id;
        } else {
            $this->execute("INSERT INTO $labels_table (type, name, updated_on) VALUES ('TaskLabel', ?, UTC_TIMESTAMP())", $label_name);

            return $this->lastInsertId();
        }
    }
}
