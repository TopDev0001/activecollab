<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('projects')->addColumns(
    [
        new DBIdColumn(),
        new DBStringColumn('based_on_type', 50),
        new DBFkColumn('based_on_id'),
        (new DBIntegerColumn('based_on_id', 10))->setUnsigned(true),
        new DBFkColumn('company_id', 0, true),
        new DBFkColumn('category_id', 0, true),
        new DBFkColumn('label_id', 0, true),
        new DBFkColumn('currency_id'),
        new DBEnumColumn(
            'budget_type',
            [
                'fixed',
                'pay_as_you_go',
                'not_billable',
            ],
            'pay_as_you_go',
        ),
        (new DBMoneyColumn('budget'))
            ->setUnsigned(true),
        new DBNameColumn(150),
        new DBFkColumn('leader_id', 0, true),
        (new DBTextColumn('body'))
            ->setSize(DBTextColumn::BIG),
        new DBActionOnByColumn('completed', true),
        new DBCreatedOnByColumn(true, true),
        new DBUpdatedOnByColumn(),
        new DBDateTimeColumn('last_activity_on'),
        (new DBIntegerColumn('project_number', 10, 0))->setUnsigned(true),
        new DBStringColumn('project_hash'),
        new DBBoolColumn('is_tracking_enabled', true),
        new DBBoolColumn('is_billable', true),
        new DBBoolColumn('members_can_change_billable', true),
        new DBBoolColumn('is_client_reporting_enabled'),
        new DBTrashColumn(),
        new DBBoolColumn('is_sample'),
    ],
)->addIndices(
    [
        new DBIndex('project_number', DBIndex::UNIQUE),
        new DBIndex('project_hash', DBIndex::UNIQUE),
    ],
);
