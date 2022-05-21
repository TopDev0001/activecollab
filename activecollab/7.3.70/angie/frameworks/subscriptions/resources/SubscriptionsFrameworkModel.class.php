<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class SubscriptionsFrameworkModel extends AngieFrameworkModel
{
    public function __construct(SubscriptionsFramework $parent)
    {
        parent::__construct($parent);

        $this->addModel(
            DB::createTable('subscriptions')->addColumns(
                [
                    new DBIdColumn(),
                    new DBParentColumn(),
                    new DBUserColumn('user'),
                    new DBDateTimeColumn('subscribed_on'),
                    DBStringColumn::create('code', 10),
                ]
            )->addIndices(
                [
                    DBIndex::create(
                        'user_subscribed',
                        DBIndex::UNIQUE,
                        [
                            'user_email',
                            'parent_type',
                            'parent_id',
                        ]
                    ),
                    DBIndex::create('subscribed_on', DBIndex::KEY, 'subscribed_on'),
                ]
            )
        );
    }
}
