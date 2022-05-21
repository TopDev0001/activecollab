<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddBillingBalanceTypeAndRowAdditionalPropertiesFileds extends AngieModelMigration
{
    public function up()
    {
      if ($this->tableExists('billing_balance_records')) {
        $billing_balance_records = $this->useTableForAlter('billing_balance_records');

        if (!$billing_balance_records->getColumn('balance_type')) {
          $billing_balance_records->addColumn(
              new DBEnumColumn('balance_type', ['subscription_fee', 'seat_fee', 'failed_payment_active_days_fee']),
              'id'
          );
        }

        if (!$billing_balance_records->getColumn('raw_additional_properties')) {
          $billing_balance_records->addColumn(
               new DBAdditionalPropertiesColumn(),
               'updated_on'
          );
        }

        $this->doneUsingTables();
      }
    }
}
