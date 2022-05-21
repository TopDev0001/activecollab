<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class RemindersFrameworkModel extends AngieFrameworkModel
{
    public function __construct(RemindersFramework $parent)
    {
        parent::__construct($parent);

        $this->addModel(
            DB::createTable('reminders')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn('CustomReminder', 50, false),
                    new DBParentColumn(),
                    new DBDateColumn('send_on'),
                    new DBTextColumn('comment'),
                    new DBCreatedOnByColumn(true, true),
                ]
            )
        )
            ->setTypeFromField('type')
            ->implementSubscriptions();
    }
}
