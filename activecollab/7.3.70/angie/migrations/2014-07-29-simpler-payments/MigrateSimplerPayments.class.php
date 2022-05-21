<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Simplify payments model.
 *
 * @package angie.migrations
 */
class MigrateSimplerPayments extends AngieModelMigration
{
    /**
     * Execute after slots update.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePaymentGatewaySlots');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $payments = $this->useTableForAlter('payments');

        $this->execute('UPDATE ' . $payments->getName() . ' SET currency_id = ? WHERE currency_id IS NULL', 0);

        $payments->alterColumn(
            'type',
            new DBTypeColumn('Payment')
        );
        $payments->alterColumn(
            'currency_id',
            DBIntegerColumn::create('currency_id', 5, 0)->setUnsigned(true)
        );

        $payments->dropColumn('gateway_type');
        $payments->dropColumn('gateway_id');

        $payments->dropColumn('reason');
        $payments->dropColumn('reason_text');

        $this->execute('UPDATE ' . $payments->getName() . ' SET type = ? WHERE type = ?', 'Payment', 'CustomPayment');
        $this->execute('UPDATE ' . $payments->getName() . ' SET type = ? WHERE type = ?', 'AuthorizePayment', 'AuthorizeNetPayment');

        $payments->addColumn(
            new DBEnumColumn(
                'status_new',
                ['paid', 'pending', 'deleted', 'canceled'],
                'pending'
            ),
            'status'
        );

        $this->execute('UPDATE ' . $payments->getName() . ' SET status_new = ? WHERE status = ?', 'paid', 'Paid');
        $this->execute('UPDATE ' . $payments->getName() . ' SET status_new = ? WHERE status = ?', 'pending', 'Pending');
        $this->execute('UPDATE ' . $payments->getName() . ' SET status_new = ? WHERE status = ?', 'deleted', 'Deleted');
        $this->execute('UPDATE ' . $payments->getName() . ' SET status_new = ? WHERE status = ?', 'canceled', 'Canceled');

        $payments->dropColumn('status');
        $payments->alterColumn(
            'status_new',
            new DBEnumColumn('status', ['paid', 'pending', 'deleted', 'canceled'], 'pending')
        );
        $payments->addIndex(DBIndex::create('status'));

        $payments->alterColumn(
            'method',
            new DBEnumColumn('method', ['paypal', 'credit_card', 'custom'], 'custom')
        );
        $payments->addColumn(new DBUpdatedOnColumn(), 'created_by_email');

        $this->execute('UPDATE ' . $payments->getName() . ' SET updated_on = created_on');

        $this->doneUsingTables();
    }
}
