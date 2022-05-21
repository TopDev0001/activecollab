<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Replace pattern based number generator with smart number generator.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateSmartInvoiceNumberGenerator extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('invoicing_number_pattern');
        $this->removeConfigOption('invoicing_number_date_counters');
        $this->removeConfigOption('invoicing_number_counter_padding');
    }
}
