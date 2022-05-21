<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add config options required for displaying release notes and upgraded warnings before running auto-upgrade.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigrateAddConfigOptionsForReleaseNotes extends AngieModelMigration
{
    /**
     * Add options.
     */
    public function up()
    {
        DB::execute('REPLACE INTO config_options (name, module, value) VALUES (?, ?, ?), (?, ?, ?)', 'release_notes', 'system', 'N;', 'upgrade_warnings', 'system', 'N;');
        AngieApplication::cache()->remove('config_options');
    }
}
