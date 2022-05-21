<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate task categories to milestones.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskCategoriesToMilestones extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        // Migration switch that converts task categories to milestones, so they can later become task lists
        if (defined('MIGRATE_TASK_CATEGORIES_TO_MILESTONES') && MIGRATE_TASK_CATEGORIES_TO_MILESTONES) {
            [$categories, $projects, $milestones, $tasks] = $this->useTables('categories', 'projects', 'milestones', 'tasks');

            $categories_map = $this->getCategoriesMap($categories);

            if (count($categories_map)) {
                if ($project_rows = $this->execute("SELECT id, name, created_on, created_by_id, created_by_name, created_by_email, completed_on, completed_by_id, completed_by_name, completed_by_email FROM $projects WHERE id IN (?) ORDER BY id", array_keys($categories_map))) {
                    foreach ($project_rows as $project_row) {
                        $project_id = $project_row['id'];

                        $next_milestone_position = $this->executeFirstCell("SELECT MAX(position) FROM $milestones WHERE project_id = ? AND start_on IS NULL AND due_on IS NULL", $project_id) + 1;

                        foreach ($categories_map[$project_id] as $category_id => $category_name) {
                            if ($project_row['completed_on'] && $this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $tasks WHERE category_id = ?", $category_id) < 1) {
                                continue; // Skip category because it is empty, in a completed project
                            }

                            $this->execute("INSERT INTO $milestones (project_id, name, created_on, created_by_id, created_by_name, created_by_email, updated_on, completed_on, completed_by_id, completed_by_name, completed_by_email, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $project_id, $category_name, $project_row['created_on'], $project_row['created_by_id'], $project_row['created_by_name'], $project_row['created_by_email'], $project_row['created_on'], $project_row['completed_on'], $project_row['completed_by_id'], $project_row['completed_by_name'], $project_row['completed_by_email'], $next_milestone_position++);
                            $this->execute("UPDATE $tasks SET milestone_id = ? WHERE category_id = ?", $this->lastInsertId(), $category_id);
                        }
                    }
                }

                $this->execute("DELETE FROM $categories WHERE type = 'TaskCategory' AND parent_type = 'Project'");
                $this->execute("UPDATE $tasks SET category_id = '0'");
            }

            $this->doneUsingTables();
        }
    }

    /**
     * Return categories mapped by project.
     *
     * @param  string $categories_table
     * @return array
     */
    private function getCategoriesMap($categories_table)
    {
        $categories_map = [];

        if ($category_rows = $this->execute("SELECT id, name, parent_id AS 'project_id' FROM $categories_table WHERE type = 'TaskCategory' AND parent_type = 'Project' ORDER BY name")) {
            foreach ($category_rows as $category_row) {
                $project_id = $category_row['project_id'];

                if (empty($categories_map[$project_id])) {
                    $categories_map[$project_id] = [];
                }

                $categories_map[$project_id][(int) $category_row['id']] = $category_row['name'];
            }
        }

        return $categories_map;
    }
}
