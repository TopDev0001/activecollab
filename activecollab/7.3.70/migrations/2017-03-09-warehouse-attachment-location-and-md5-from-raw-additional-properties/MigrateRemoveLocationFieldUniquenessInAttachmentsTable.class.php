<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveLocationFieldUniquenessInAttachmentsTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('attachments')) {
            $attachments = $this->useTableForAlter('attachments');
            if ($attachments->indexExists('location')) {
                $attachments->alterIndex('location', DBIndex::create('location'));
            }
            $this->doneUsingTables();
        }
    }
}
