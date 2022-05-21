<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddRawAdditionalPropertiesToUploadedFiles extends AngieModelMigration
{
    public function up()
    {
        $remote_invoices = $this->useTableForAlter('uploaded_files');

        $remote_invoices->addColumn(new DBAdditionalPropertiesColumn(), 'code');

        $this->doneUsingTables();
    }
}
