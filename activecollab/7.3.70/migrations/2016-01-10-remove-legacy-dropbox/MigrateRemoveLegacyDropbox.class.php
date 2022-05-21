<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove legacy Dropbox experiment.
 *
 * @package ActiveCollab.migrations
 */
class MigrateRemoveLegacyDropbox extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('dropbox_entries')) {
            $this->dropTable('dropbox_entries');
        }

        $this->removeConfigOption('dropbox_token');
        $this->removeConfigOption('dropbox_last_refresh_on');
        $this->removeConfigOption('dropbox_cursor');
        $this->removeConfigOption('dropbox_configured_by_id');
        $this->removeConfigOption('dropbox_remote_folder_path');
    }
}
