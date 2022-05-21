<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove state field from attachments.
 *
 * @package angie.migrations
 */
class MigrateRemoveStateFromAttachments extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $attachments = $this->useTableForAlter('attachments');

        $attachments->dropColumn('state');
        $attachments->dropColumn('original_state');

        $this->doneUsingTables();
    }
}
