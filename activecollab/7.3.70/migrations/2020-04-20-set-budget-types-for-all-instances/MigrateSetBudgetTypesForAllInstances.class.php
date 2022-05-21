<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateSetBudgetTypesForAllInstances extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setConfigOptionValue('project_budgeting_setup',
                [
                    'completed_on' => null,
                    'completed_by_id' => null,
                    'completed_by_name' => null,
                    'completed_by_email' => null,
                ]
            );
        }

        $is_migration_already_done = AngieApplication::memories()->get('is_migration_for_budget_types_onboarding_executed', false);

        if (!$is_migration_already_done) {
            // migrate projects with budget > 0 to fixed type budget type
            DB::execute('
                UPDATE projects
                SET budget_type = "fixed", updated_on = NOW()
                WHERE budget > 0 AND is_trashed = 0 AND (completed_on IS NULL OR completed_on BETWEEN "2020-01-01" AND NOW())
            ');

            // all projects with disabled time tracking will be set to not_billable budget type
            DB::execute('
                UPDATE projects
                SET budget_type = "not_billable", updated_on = NOW()
                WHERE is_tracking_enabled = 0
            ');

            // if an instance doesnt have any projects with enabled budgeting and time and expense tracking, set that budget types onboarding is done
            $projects_with_enabled_tracking = DB::executeFirstCell('
                SELECT COUNT(*)
                FROM projects
                WHERE is_tracking_enabled = 1 AND is_trashed = 0 AND (completed_on IS NULL OR completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND NOW())
            ');
            if (!$projects_with_enabled_tracking) {
                $firstOwner = Users::findFirstOwner();
                $this->setConfigOptionValue('project_budgeting_setup',
                    [
                        'completed_on' => (DateTimeValue::now())->toMySQL(),
                        'completed_by_id' => $firstOwner->getId(),
                        'completed_by_name' => $firstOwner->getDisplayName(true),
                        'completed_by_email' => $firstOwner->getEmail(),
                    ]
                );
            }

            AngieApplication::memories()->set('is_migration_for_budget_types_onboarding_executed', true);
        }
    }
}
