<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateSetDiscountNameBasedOnThePercent extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            $table = $this->useTableForAlter('billing_orders');

            if (!$table->getColumn('discount_name')) {
                $table->addColumn(
                    new DBStringColumn('discount_name'),
                    'discount_rate'
                );
            }

            $this->doneUsingTables();

            $this->execute('UPDATE `billing_orders` SET `discount_name` = ? WHERE `discount_rate` = ? AND (`discount_name` IS NULL OR `discount_name` = "")',
                'non_profit',
                50
            );
        }
    }
}
