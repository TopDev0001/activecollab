<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate permanently deleted projects.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigratePermanentlyDeletedProjects extends AngieModelMigration
{
    /**
     * Execute when project objects are cleaned up.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePermanentlyDeletedProjectObjects');
    }

    /**
     * Migreate up.
     */
    public function up()
    {
        [$projects, $categories, $config_option_values, $project_users, $favorites] = $this->useTables('projects', 'categories', 'config_option_values', 'project_users', 'favorites');

        defined('STATE_DELETED') or define('STATE_DELETED', 0);

        if ($project_ids = $this->executeFirstColumn("SELECT id FROM $projects WHERE state = ?", STATE_DELETED)) {
            $escaped_project_ids = DB::escape($project_ids);

            $this->execute("DELETE FROM $categories WHERE parent_type = 'Project' AND parent_id IN ($escaped_project_ids)");
            $this->execute("DELETE FROM $config_option_values WHERE parent_type = 'Project' AND parent_id IN ($escaped_project_ids)");
            $this->execute("DELETE FROM $project_users WHERE project_id IN ($escaped_project_ids)");
            $this->execute("DELETE FROM $favorites WHERE parent_type = 'Project' AND parent_id IN ($escaped_project_ids)");

            $this->execute("DELETE FROM $projects WHERE id IN ($escaped_project_ids)");
        }
    }
}
