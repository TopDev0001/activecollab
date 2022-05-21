<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateDisconnectShXero extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $xeroIntegration = Integrations::findFirstByType(XeroIntegration::class);
            if ($xeroIntegration && $xeroIntegration->isInUse()) {
                $xeroIntegration->delete();
            }
        }
    }
}
