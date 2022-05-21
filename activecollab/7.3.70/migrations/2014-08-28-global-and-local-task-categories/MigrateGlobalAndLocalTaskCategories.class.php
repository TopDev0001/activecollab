<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate global and local task categories.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateGlobalAndLocalTaskCategories extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$categories, $tasks] = $this->useTables('categories', 'tasks');

        $this->execute("UPDATE $tasks SET category_id = '0' WHERE category_id IS NULL");

        $master_categories = $this->getConfigOptionValue('task_categories');
        $master_categories = is_array($master_categories) ? array_unique($master_categories) : [];

        if (count($master_categories)) {
            [$owner_id, $owner_name, $owner_email, $owner_created_on] = $this->getFirstUsableOwner();

            $owner_id = DB::escape($owner_id);
            $owner_name = DB::escape($owner_name);
            $owner_email = DB::escape($owner_email);

            foreach ($master_categories as $master_category) {
                $category_ids = $this->executeFirstColumn("SELECT id FROM $categories WHERE type = 'TaskCategory' AND name = ?", $master_category);

                if ($category_ids && $this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $tasks WHERE category_id IN (?)", $category_ids)) {
                    $this->execute("INSERT INTO $categories (type, name, created_on, created_by_id, created_by_name, created_by_email, updated_on) VALUES ('GlobalTaskCategory', ?, UTC_TIMESTAMP(), $owner_id, $owner_name, $owner_email, UTC_TIMESTAMP())", $master_category);
                    $this->execute("UPDATE $tasks SET category_id = ? WHERE category_id IN (?)", $this->lastInsertId(), $category_ids);
                }
            }
        }

        if ($local_category_ids = $this->executeFirstColumn("SELECT id FROM $categories WHERE type = 'TaskCategory'")) {
            $this->execute("DELETE FROM $categories WHERE type = 'TaskCategory' AND id NOT IN (SELECT DISTINCT category_id FROM $tasks WHERE category_id > 0)");
            $this->execute("UPDATE $categories SET type = 'LocalTaskCategory' WHERE type = 'TaskCategory'");
        }

        $this->doneUsingTables();

        $this->removeConfigOption('task_categories');
    }
}
