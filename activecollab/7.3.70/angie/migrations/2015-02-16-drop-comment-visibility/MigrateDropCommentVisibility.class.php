<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop comment visibility.
 *
 * @package angie.migrations
 */
class MigrateDropCommentVisibility extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $comments = $this->useTableForAlter('comments');

        if ($comments->getColumn('visibility')) {
            $comments->dropColumn('visibility');
        }

        if ($comments->getColumn('original_visibility')) {
            $comments->dropColumn('original_visibility');
        }

        $this->doneUsingTables();
    }
}
