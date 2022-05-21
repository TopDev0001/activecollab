<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTypeColumnToUploadedFilesTable extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('uploaded_files')->addColumn(
            new DBTypeColumn('LocalUploadedFile'),
            'id'
        );
    }
}
