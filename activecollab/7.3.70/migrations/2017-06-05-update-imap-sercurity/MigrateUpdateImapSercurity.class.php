<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateImapSercurity extends AngieModelMigration
{
    public function up()
    {
        $integrations = $this->useTables('integrations')[0];

        if (!AngieApplication::isOnDemand() && $email_settings = $this->execute("SELECT id, raw_additional_properties FROM $integrations WHERE type = 'EmailIntegration'")) {
            foreach ($email_settings as $setting) {
                $properties = $setting['raw_additional_properties'] ? unserialize($setting['raw_additional_properties']) : [];

                if ((bool) $setting['raw_additional_properties']) {
                    if (isset($properties['imap_security']) && $properties['imap_security'] == 'off') {
                        $properties['imap_security'] = 'auto';

                        $this->execute("UPDATE $integrations SET raw_additional_properties = ? WHERE id = ?", serialize($properties), $setting['id']);
                    }
                }
            }
        }
    }
}
