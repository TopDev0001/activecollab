<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddBudgetThresholdsModel extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('budget_thresholds')) {
            $this->createTable(
                DB::createTable('budget_thresholds')
                    ->addColumns(
                        [
                            new DBIdColumn(),
                            DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                            new DBEnumColumn(
                                'type',
                                [
                                    'income',
                                    'cost',
                                    'profit',
                                ],
                                'income'),
                            DBIntegerColumn::create('threshold', 10, 0)->setUnsigned(true),
                            new DBCreatedOnByColumn(),
                        ]
                    )
            );
        }
    }
}
