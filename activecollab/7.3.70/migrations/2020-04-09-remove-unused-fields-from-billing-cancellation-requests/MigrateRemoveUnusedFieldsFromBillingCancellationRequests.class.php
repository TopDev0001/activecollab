<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRemoveUnusedFieldsFromBillingCancellationRequests extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_cancellation_requests')) {
            $billing_cancellation_requests_table = $this->useTableForAlter('billing_cancellation_requests');
            $this->execute('UPDATE ' . $billing_cancellation_requests_table->getName() . ' SET status = ? WHERE status = ?', 'feedback', 'password_verified');

            if ($billing_cancellation_requests_table->indexExists('hash')) {
                $billing_cancellation_requests_table->dropIndex('hash');
            }

            if ($billing_cancellation_requests_table->getColumn('hash')) {
                $billing_cancellation_requests_table->dropColumn('hash');
            }

            if ($billing_cancellation_requests_table->getColumn('expired_at')) {
                $billing_cancellation_requests_table->dropColumn('expired_at');
            }

            if ($billing_cancellation_requests_table->getColumn('status')) {
                $possibilities = [
                    'created',
                    'feedback',
                    'confirmed',
                    'canceled',
                ];

                $billing_cancellation_requests_table->alterColumn(
                    'status',
                    new DBEnumColumn(
                        'status',
                        $possibilities,
                        null
                    )
                );
            }

            $this->doneUsingTables();
        }
    }
}
