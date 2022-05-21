<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddUploadedFiles extends AngieModelMigration
{
    public function up()
    {
        $this->createTable(
            DB::createTable('uploaded_files')->addColumns(
                [
                    new DBIdColumn(),
                    new DBFileMetaColumn(),
                    new DBStringColumn('code', 40),
                    new DBCreatedOnByColumn(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('code', DBIndex::UNIQUE),
                ]
            )
        );
    }
}
