<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

function files_handle_on_initial_settings(array &$settings): void
{
    $settings['google_drive'] = [
        'client_id' => Integrations::findFirstByType(GoogleDriveIntegration::class)->getClientId(),
        'app_id' => Integrations::findFirstByType(GoogleDriveIntegration::class)->getAppId(),
    ];

    $settings['dropbox_app_key'] = Integrations::findFirstByType(DropboxIntegration::class)->getAppKey();
}
