<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate invoices - add hash field.
 *
 * @package ActiveCollab.migrations
 */
class MigrateInvoices extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('invoice_objects')) {
            $invoice_object_table = $this->useTableForAlter('invoice_objects');
            $invoice_object_table->addColumn(DBStringColumn::create('hash', 50));
            $this->doneUsingTables();

            $invoices = $this->execute('SELECT id FROM invoice_objects WHERE type IN (?)', ['Invoice', 'Quote']);
            if (is_foreachable($invoices)) {
                foreach ($invoices as $invoice) {
                    do {
                        $string = microtime();
                        $hash = substr(sha1($string), 0, 20);
                    } while ($this->executeFirstCell('SELECT id FROM invoice_objects WHERE hash = ?', $hash) != null);
                    $this->execute('UPDATE invoice_objects SET hash = ? WHERE id = ?', $hash, $invoice['id']);
                }
            }
        }
    }
}
