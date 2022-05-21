<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateStatusColumnForTableBillingCancellationRequests extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_cancellation_requests')) {
            $billing_cancellation_requests = $this->useTableForAlter('billing_cancellation_requests');

            if ($billing_cancellation_requests->getColumn('status')) {
                $billing_cancellation_requests->alterColumn(
                    'status',
                    new DBEnumColumn(
                        'status',
                        [
                            'created',
                            'feedback',
                            'password_verified',
                            'confirmed',
                            'canceled',
                        ]
                    )
                );
            }
        }
    }
}
