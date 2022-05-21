<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update project users.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateUpdateProjectUsersRelation extends AngieModelMigration
{
    /**
     * Execute after given migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateProjectOverviewToBody');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('project_roles');

        $project_users = $this->useTableForAlter('project_users');

        $project_users->dropColumn('permissions');
        $project_users->dropColumn('role_id');
    }
}
