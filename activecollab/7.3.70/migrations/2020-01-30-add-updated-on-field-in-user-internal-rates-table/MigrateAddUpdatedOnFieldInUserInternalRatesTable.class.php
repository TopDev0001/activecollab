<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddUpdatedOnFieldInUserInternalRatesTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('user_internal_rates')) {
            $internal_rates = $this->useTableForAlter('user_internal_rates');
            if (!$internal_rates->getColumn('updated_on')) {
                $internal_rates->addColumn(new DBUpdatedOnByColumn());
            }
            $this->execute('UPDATE ' . $internal_rates->getName() . ' SET updated_on = created_on, updated_by_id = created_by_id, updated_by_name = created_by_name, updated_by_email = created_by_email');
            $this->doneUsingTables();
        }
    }
}
