<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddIntegrationForXero extends AngieModelMigration
{
    public function up()
    {
        if (!$this->execute("SELECT * FROM `integrations` WHERE `type` = 'XeroIntegration'")) {
            $this->execute("INSERT INTO `integrations` (`type`) VALUES ('XeroIntegration')");
        }
    }
}
