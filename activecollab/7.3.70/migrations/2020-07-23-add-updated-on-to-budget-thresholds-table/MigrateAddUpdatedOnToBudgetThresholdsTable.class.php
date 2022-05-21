<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddUpdatedOnToBudgetThresholdsTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('budget_thresholds')) {
            $budget_thresholds = $this->useTableForAlter('budget_thresholds');

            if (!$budget_thresholds->getColumn('updated_on')) {
                $budget_thresholds->addColumn(new DBUpdatedOnColumn());
                $this->execute("UPDATE {$budget_thresholds->getName()} SET updated_on = UTC_TIMESTAMP()");
            }
        }
    }
}
