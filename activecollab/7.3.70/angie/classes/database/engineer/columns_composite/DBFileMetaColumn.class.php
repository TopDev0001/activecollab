<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBFileMetaColumn extends DBCompositeColumn
{
    public function __construct()
    {
        $this->columns = [
            new DBNameColumn(150),
            DBStringColumn::create('mime_type', DBStringColumn::MAX_LENGTH, 'application/octet-stream'),
            DBIntegerColumn::create('size', 10, 0)->setUnsigned(true),
            DBStringColumn::create('location', DBStringColumn::MAX_LENGTH),
            DBStringColumn::create('md5', 32),
        ];
    }

    public function addedToTable(): void
    {
        $this->table->addModelTrait('IFile', 'IFileImplementation');
        $this->table->addIndex(DBIndex::create('location'));

        parent::addedToTable();
    }
}
