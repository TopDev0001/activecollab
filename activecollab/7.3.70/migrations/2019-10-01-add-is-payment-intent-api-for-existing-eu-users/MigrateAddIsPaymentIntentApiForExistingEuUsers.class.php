<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsPaymentIntentApiForExistingEuUsers extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_payment_methods')) {
            $eu_countries = array_keys((new Countries())->getEuCountries());

            $this->executeFirstCell('UPDATE `billing_payment_methods`  SET  `is_payment_intent_api` = 1, `is_3d_secured` = 1 WHERE `country` IN (?)', $eu_countries);
        }
    }
}
