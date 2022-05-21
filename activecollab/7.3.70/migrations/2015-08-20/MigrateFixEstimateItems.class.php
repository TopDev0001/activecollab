<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Rename parent_type from Quote to Estimate.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage estimates
 */
class MigrateFixEstimateItems extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute("UPDATE invoice_items SET parent_type = 'Estimate' WHERE parent_type = 'Quote'");
    }
}
