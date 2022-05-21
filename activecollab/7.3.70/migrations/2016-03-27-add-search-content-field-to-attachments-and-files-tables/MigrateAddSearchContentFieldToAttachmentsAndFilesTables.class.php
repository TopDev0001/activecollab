<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddSearchContentFieldToAttachmentsAndFilesTables extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('attachments')->addColumn(
            (new DBTextColumn('search_content'))
                ->setSize(DBTextColumn::BIG),
            'raw_additional_properties'
        );

        $this->useTableForAlter('files')->addColumn(
            (new DBTextColumn('search_content'))
                ->setSize(DBTextColumn::BIG),
            'raw_additional_properties'
        );
    }
}
