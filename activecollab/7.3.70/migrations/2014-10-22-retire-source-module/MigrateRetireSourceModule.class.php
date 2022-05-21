<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Retire source module.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigrateRetireSourceModule extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (['source_repositories', 'source_paths', 'source_users', 'source_commits', 'commit_project_objects', 'search_index_for_source'] as $table) {
            $this->dropTable($table);
        }

        $this->removeConfigOption('source_svn_type');
        $this->removeConfigOption('source_svn_path');
        $this->removeConfigOption('source_svn_config_dir');
        $this->removeConfigOption('source_svn_trust_server_cert');
        $this->removeConfigOption('source_mercurial_path');
        $this->removeConfigOption('default_source_branch');
        $this->removeConfigOption('source_mercurial_path');
        $this->removeConfigOption('source_mercurial_path');
    }
}
