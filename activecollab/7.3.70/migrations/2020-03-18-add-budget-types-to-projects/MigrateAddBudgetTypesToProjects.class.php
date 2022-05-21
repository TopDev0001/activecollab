<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddBudgetTypesToProjects extends AngieModelMigration
{
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        if (!$projects->getColumn('budget_type')) {
            $projects->addColumn(
                new DBEnumColumn(
                    'budget_type',
                    [
                        'fixed',
                        'pay_as_you_go',
                        'not_billable',
                    ],
                    'pay_as_you_go'
                ),
                'currency_id'
            );
        }
    }
}
