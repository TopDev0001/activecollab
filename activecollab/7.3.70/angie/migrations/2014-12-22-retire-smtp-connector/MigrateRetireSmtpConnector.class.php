<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Retire SMTP connector.
 *
 * @package angie.migrations
 */
class MigrateRetireSmtpConnector extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('mailing_smtp_authenticate');
        $this->removeConfigOption('mailing_smtp_host');
        $this->removeConfigOption('mailing_smtp_username');
        $this->removeConfigOption('mailing_smtp_password');
        $this->removeConfigOption('mailing_smtp_port');
        $this->removeConfigOption('mailing_smtp_security');

        if ($this->getConfigOptionValue('mailing') == 'smtp') {
            $this->setConfigOptionValue('mailing', 'native');
        }
    }
}
