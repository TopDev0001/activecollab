<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Clean up old activeCollab data (old tables, caches etc).
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateCleanUpOldActivecollabData extends AngieModelMigration
{
    /**
     * Migreate up.
     */
    public function up()
    {
        [$routing_cache] = $this->useTables('routing_cache');

        $this->execute("TRUNCATE TABLE $routing_cache");

        foreach (['assignment_filters', 'content_backup', 'tags_backup', 'update_logs', 'helpdesk_conversations', 'milestone_filters', 'permissions', 'project_object_views', 'tracking_reports', 'user_roles'] as $table_name) {
            if ($this->tableExists($table_name)) {
                $this->dropTable($table_name);
            }
        }

        $this->doneUsingTables();
    }
}
