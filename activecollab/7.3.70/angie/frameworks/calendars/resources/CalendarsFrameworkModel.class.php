<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

class CalendarsFrameworkModel extends AngieFrameworkModel
{
    public function __construct(CalendarsFramework $parent)
    {
        parent::__construct($parent);

        $this->addModel(
            DB::createTable('calendars')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn('UserCalendar'),
                    new DBNameColumn(255),
                    DBStringColumn::create('color', 7),
                    new DBAdditionalPropertiesColumn(),
                    new DBCreatedOnByColumn(true),
                    new DBUpdatedOnColumn(),
                    new DBTrashColumn(),
                    DBIntegerColumn::create('position', 10, '0')->setUnsigned(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('position'),
                ]
            )
        )
            ->setOrderBy('position')
            ->implementTrash()
            ->setTypeFromField('type')
            ->implementMembers(true)
            ->implementHistory()
            ->implementActivityLog()
            ->implementActivityLog()
            ->addModelTrait(ICalendarFeed::class, ICalendarFeedImplementation::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('calendar_events')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('calendar_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    new DBNameColumn(255),
                    new DBDateColumn('starts_on'),
                    new DBTimeColumn('starts_on_time'),
                    new DBDateColumn('ends_on'),
                    new DBTimeColumn('ends_on_time'),
                    new DBEnumColumn(
                        'repeat_event',
                        [
                            'dont',
                            'daily',
                            'weekly',
                            'monthly',
                            'yearly',
                        ],
                        'dont'
                    ),
                    new DBDateColumn('repeat_until'),
                    new DBAdditionalPropertiesColumn(),
                    new DBCreatedOnByColumn(true),
                    new DBUpdatedOnColumn(),
                    new DBTrashColumn(true),
                    (new DBTextColumn('note'))
                        ->setSize(DBTextColumn::BIG),
                    DBIntegerColumn::create('position', 10, '0')->setUnsigned(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('starts_on'),
                    DBIndex::create('starts_on_time', DBIndex::KEY, ['starts_on', 'starts_on_time']),
                    DBIndex::create('ends_on'),
                    DBIndex::create('ends_on_time', DBIndex::KEY, ['ends_on', 'ends_on_time']),
                    DBIndex::create('position'),
                ]
            )
        )
            ->setOrderBy('starts_on, starts_on_time, position')
            ->implementTrash()
            ->implementSubscriptions()
            ->implementAccessLog()
            ->implementHistory()
            ->implementActivityLog()
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addTable(DB::createTable('calendar_users')->addColumns([
            DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0),
            DBIntegerColumn::create('calendar_id', DBColumn::NORMAL, 0),
        ])->addIndices([
            new DBIndexPrimary(['user_id', 'calendar_id']),
        ]));
    }

    /**
     * Load initial data.
     */
    public function loadInitialData()
    {
        $this->addConfigOption('hidden_calendars');
        $this->addConfigOption('hidden_projects_on_calendar');
        $this->addConfigOption('calendar_sidebar_hidden');
        $this->addConfigOption('default_project_calendar_filter', 'everything_in_my_projects');
        $this->addConfigOption('calendar_mode', 'monthly');

        parent::loadInitialData();
    }
}
