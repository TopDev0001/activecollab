<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddNewFieldsToInvoices extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('invoices')) {
            $invoices = $this->useTableForAlter('invoices');
            if (!$invoices->getColumn('qr_note')) {
                $invoices->addColumn(
                    new DBEnumColumn('qr_note', ['none', 'payment_url', 'custom'], 'none'),
                    'private_note'
                );
            }

            if (!$invoices->getColumn('qr_note_content')) {
                $invoices->addColumn(
                    new DBTextColumn('qr_note_content'),
                'qr_note'
                );
            }
        }

        $this->doneUsingTables();
    }
}
