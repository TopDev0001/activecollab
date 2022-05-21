<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddNewColumnsInBudgetThresholdsNotifications extends AngieModelMigration
{
    /**
     * Execute after.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateAddTwoNewFieldsToBudgetThresholds');
    }

    public function up()
    {
        if ($this->tableExists('budget_thresholds_notifications')) {
            $budget_thresholds_notifications = $this->useTableForAlter('budget_thresholds_notifications');

            if (!$budget_thresholds_notifications->getColumn('type')) {
                $budget_thresholds_notifications->addColumn(
                    new DBEnumColumn(
                        'type',
                        [
                            'income',
                            'const',
                            'profit',
                        ],
                        'income'
                    ),
                    'id'
                );
            }

            if (!$budget_thresholds_notifications->getColumn('threshold')) {
                $budget_thresholds_notifications->addColumn(
                    DBIntegerColumn::create('threshold', 10, 0)->setUnsigned(true),
                    'type'
                );
                $update_threshold = '
                    UPDATE budget_thresholds_notifications, budget_thresholds
                    SET budget_thresholds_notifications.threshold = budget_thresholds.threshold
                    WHERE budget_thresholds_notifications.parent_id = budget_thresholds.id
                ';
                $this->execute($update_threshold);
            }

            if (!$budget_thresholds_notifications->getColumn('project_id')) {
                $budget_thresholds_notifications->addColumn(
                    DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                    'id'
                );
                $update_project_id = '
                    UPDATE budget_thresholds_notifications, budget_thresholds
                    SET budget_thresholds_notifications.project_id = budget_thresholds.project_id
                    WHERE budget_thresholds_notifications.parent_id = budget_thresholds.id
                ';
                $this->execute($update_project_id);
            }

            if ($budget_thresholds_notifications->getColumn('parent_id')) {
                $budget_thresholds_notifications->dropColumn('parent_id');
            }
        }
    }
}
