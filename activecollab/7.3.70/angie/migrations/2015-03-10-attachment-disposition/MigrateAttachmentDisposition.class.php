<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAttachmentDisposition extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('attachments')->addColumn(
            new DBEnumColumn('disposition', ['attachment', 'inline'], 'attachment'),
            'md5'
        );
        $this->doneUsingTables();
    }
}
