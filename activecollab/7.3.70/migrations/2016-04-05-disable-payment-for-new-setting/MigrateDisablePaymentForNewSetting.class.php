<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateDisablePaymentForNewSetting extends AngieModelMigration
{
    public function up()
    {
        $this->execute("UPDATE payment_gateways pg SET pg.raw_additional_properties = null, pg.is_enabled = 0 WHERE pg.type IN ('StripeGateway', 'PaypalDirectGateway')");
    }
}
