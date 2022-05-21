<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateProjectIdForAttachments extends AngieModelMigration
{
    public function up()
    {
        $attachments = $this->useTableForAlter('attachments');

        $attachments->addColumn(
            DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
            'id'
        );
        $attachments->addColumn(
            new DBBoolColumn('is_hidden_from_clients'),
            'project_id'
        );

        $attachments->addIndex(DBIndex::create('project_id'));

        $this->doneUsingTables();
    }
}
