<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRenameColumnRemoteInvoiceRemoteId extends AngieModelMigration
{
    public function up()
    {
        if ($this->execute("SHOW COLUMNS FROM `remote_invoices` LIKE 'remote_id'")) {
            $this->execute('ALTER TABLE `remote_invoices` CHANGE COLUMN `remote_id` `remote_code` VARCHAR(100);');
        }
    }
}
