<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add show visual editor toolbar.
 *
 * @package angie.migrations
 */
class MigrateAddShowVisualEditorToolbar extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('show_visual_editor_toolbar', false, false);
    }
}
