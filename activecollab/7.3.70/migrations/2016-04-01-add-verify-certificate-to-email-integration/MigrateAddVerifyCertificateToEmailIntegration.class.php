<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddVerifyCertificateToEmailIntegration extends AngieModelMigration
{
    public function up()
    {
        $integrations = $this->useTables('integrations')[0];

        if ($email_settings = $this->execute("SELECT id, raw_additional_properties FROM $integrations WHERE type = 'EmailIntegration'")) {
            foreach ($email_settings as $setting) {
                $properties = $setting['raw_additional_properties'] ? unserialize($setting['raw_additional_properties']) : [];

                if ((bool) $setting['raw_additional_properties']) {
                    if (defined('VERIFY_SMTP_SSL') && !VERIFY_SMTP_SSL && !isset($properties['smtp_verify_certificate'])) {
                        $properties['smtp_verify_certificate'] = false;
                    } else {
                        $properties['smtp_verify_certificate'] = true;
                    }

                    if (defined('VERIFY_IMAP_SSL') && !VERIFY_IMAP_SSL && !isset($properties['imap_verify_certificate'])) {
                        $properties['imap_verify_certificate'] = false;
                    } else {
                        $properties['imap_verify_certificate'] = true;
                    }
                } else {
                    unset($properties['imap_verify_certificate']);
                    unset($properties['smtp_verify_certificate']);
                }

                $this->execute("UPDATE $integrations SET raw_additional_properties = ? WHERE id = ?", serialize($properties), $setting['id']);
            }
        }
    }
}
