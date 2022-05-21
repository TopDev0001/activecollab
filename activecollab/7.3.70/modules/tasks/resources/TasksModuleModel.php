<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Resources;

use ActiveCollab\Module\Tasks\TasksModule;
use ActiveCollabModuleModel;
use AngieApplicationModel;
use DB;
use DBBoolColumn;
use IHiddenFromClients;
use IInvoiceBasedOn;
use IInvoiceBasedOnTrackedDataImplementation;
use IProjectElement;
use IProjectElementImplementation;
use ISubtasks;
use ISubtasksImplementation;
use ITaskDependencies;
use ITaskDependenciesImplementation;
use ITracking;
use ITrackingImplementation;
use IWhoCanSeeThis;
use IWhoCanSeeThisImplementation;
use TaskLabel;

require_once APPLICATION_PATH . '/resources/ActiveCollabModuleModel.class.php';

class TasksModuleModel extends ActiveCollabModuleModel
{
    public function __construct(TasksModule $parent)
    {
        parent::__construct($parent);

        $this
            ->addModelFromFile('task_lists')
            ->setOrderBy('position')
            ->implementHistory()
            ->implementTrash()
            ->implementComplete()
            ->implementSearch()
            ->implementActivityLog()
            ->addModelTrait(IProjectElement::class, IProjectElementImplementation::class)
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class)
            ->addModelTrait(IInvoiceBasedOn::class, IInvoiceBasedOnTrackedDataImplementation::class)
            ->addModelTraitTweak('IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation');

        $this
            ->addModelFromFile('tasks')
            ->implementAssignees()
            ->implementComplete()
            ->implementHistory()
            ->implementAccessLog()
            ->implementComments(true, true)
            ->implementAttachments()
            ->implementLabels()
            ->implementTrash()
            ->implementSearch()
            ->implementActivityLog()
            ->implementReminders()
            ->addModelTrait(IHiddenFromClients::class)
            ->addModelTrait(ITaskDependencies::class, ITaskDependenciesImplementation::class)
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class)
            ->addModelTrait(IProjectElement::class, IProjectElementImplementation::class)
            ->addModelTrait(ITracking::class, ITrackingImplementation::class)
            ->addModelTrait(IInvoiceBasedOn::class, IInvoiceBasedOnTrackedDataImplementation::class)
            ->addModelTraitTweak('IProjectElementImplementation::canViewAccessLogs insteadof IAccessLogImplementation')
            ->addModelTraitTweak('IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation');

        $this->addModelFromFile('task_dependencies');

        $this
            ->addModelFromFile('subtasks')
            ->implementAssignees()
            ->implementComplete()
            ->implementHistory()
            ->implementTrash()
            ->implementActivityLog()
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class)
            ->setOrderBy('ISNULL(position) ASC, position, created_on');

        $this
            ->addModelFromFile('recurring_tasks')
            ->implementAssignees()
            ->implementHistory()
            ->implementAccessLog()
            ->implementSubscriptions()
            ->implementAttachments()
            ->implementLabels()
            ->implementSearch()
            ->implementTrash()
            ->implementActivityLog()
            ->addModelTrait(IHiddenFromClients::class)
            ->addModelTrait(ISubtasks::class, ISubtasksImplementation::class)
            ->addModelTrait(IProjectElement::class, IProjectElementImplementation::class)
            ->addModelTraitTweak('IProjectElementImplementation::canViewAccessLogs insteadof IAccessLogImplementation')
            ->addModelTraitTweak('IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation');

        $this->addTableFromFile('custom_hourly_rates');

        // Add is_global field to labels model
        AngieApplicationModel::getTable('labels')
            ->addColumn(
                new DBBoolColumn('is_global'),
                'is_default',
            );
    }

    /**
     * Load initial framework data.
     */
    public function loadInitialData()
    {
        $this->addConfigOption('task_options', []);
        $this->addConfigOption('show_project_id', false);
        $this->addConfigOption('show_task_id', false);
        $this->addConfigOption('task_estimates_enabled', true);
        $this->addConfigOption('task_estimates_enabled_lock', false);
        $this->addConfigOption('show_task_estimates_to_clients', false);
        $this->addConfigOption('display_mode_project_tasks', 'list');
        $this->addConfigOption('skip_days_off_when_rescheduling', true);

        // Task Dependencies Feature
        $this->addConfigOption('task_dependencies_enabled', true);
        $this->addConfigOption('task_dependencies_enabled_lock', true);

        // Auto-Reschedule Feature
        $this->addConfigOption('auto_reschedule_enabled', true);
        $this->addConfigOption('auto_reschedule_enabled_lock', true);

        // Timeline Feature
        $this->addConfigOption('timeline_enabled', true);
        $this->addConfigOption('timeline_enabled_lock', true);

        // Recurring Tasks Feature
        $this->addConfigOption('recurring_tasks_enabled', true);
        $this->addConfigOption('recurring_tasks_enabled_lock', true);

        // Label promotion
        $this->addConfigOption('auto_promote_task_labels', true);

        DB::execute("ALTER TABLE tasks ADD created_from_discussion_id INT UNSIGNED NOT NULL DEFAULT '0'");

        $labels = [
            ['NEW', '#C3E799'],
            ['CONFIRMED', '#FBBB75'],
            ['WORKS FOR ME', '#C3E799'],
            ['DUPLICATE', '#C3E799'],
            ['WONT FIX', '#C3E799'],
            ['ASSIGNED', '#FF9C9C'],
            ['BLOCKED', '#DDDDDD'],
            ['IN PROGRESS', '#C3E799'],
            ['FIXED', '#BEACF9'],
            ['REOPENED', '#FF9C9C'],
            ['VERIFIED', '#C3E799'],
        ];

        $counter = 1;
        $to_insert = [];

        foreach ($labels as $label) {
            $to_insert[] = DB::prepare(
                '(?, ?, ?, ?, ?)',
                TaskLabel::class,
                $label[0],
                $label[1],
                true,
                $counter++,
            );
        }

        DB::execute(
            sprintf(
                'INSERT INTO labels (type, name, color, is_global, position) VALUES %s',
                implode(', ', $to_insert),
            ),
        );

        parent::loadInitialData();
    }
}
