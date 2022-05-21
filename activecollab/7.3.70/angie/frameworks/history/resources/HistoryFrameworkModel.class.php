<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class HistoryFrameworkModel extends AngieFrameworkModel
{
    public function __construct(HistoryFramework $parent)
    {
        parent::__construct($parent);

        $this->addModel(
            DB::createTable('modification_logs')->addColumns(
                [
                    (new DBIdColumn())
                        ->setSize(DBColumn::BIG),
                    new DBParentColumn(),
                    new DBCreatedOnByColumn(true),
                ]
            )
        )->setOrderBy('created_on');

        $this->addTable(
            DB::createTable('modification_log_values')->addColumns(
                [
                    DBIntegerColumn::create('modification_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBStringColumn::create('field', 50, ''),
                    (new DBTextColumn('old_value'))
                        ->setSize(DBColumn::BIG),
                    (new DBTextColumn('new_value'))
                        ->setSize(DBColumn::BIG),
                ]
            )->addIndices(
                [
                    new DBIndexPrimary(['modification_id', 'field']),
                ]
            )
        );
    }
}
