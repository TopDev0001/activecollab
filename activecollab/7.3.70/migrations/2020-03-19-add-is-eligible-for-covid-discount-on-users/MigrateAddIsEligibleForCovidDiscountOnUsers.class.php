<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsEligibleForCovidDiscountOnUsers extends AngieModelMigration
{
    public function up()
    {
        $users_table = $this->useTableForAlter('users');

        if (!$users_table->getColumn('is_eligible_for_covid_discount')) {
            $users_table->addColumn(
                new DBBoolColumn(
                    'is_eligible_for_covid_discount',
                    1
                ),
                'raw_additional_properties'
            );
        }
    }
}
