<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * notification_logo helper implementation.
 *
 * @package angie.frameworks.email
 * @subpackage helpers
 */

use ActiveCollab\Module\System\Wires\NotificationLogoProxy;

/**
 * Render notification logo.
 *
 * @return string
 */
function smarty_function_notification_logo()
{
    return AngieApplication::cache()->get('notification_logo_html', function () {
        $file_path = APPLICATION_PATH . '/resources/notification-logo-2x.png';

        if (!is_file($file_path)) {
            $file_path = NotificationsFramework::PATH . '/resources/notification-logo-2x.png';
        }

        $image_size = getimagesize($file_path);

        return '<!-- Logo -->' . "\n" .
        '<br><img src="' . clean(AngieApplication::getProxyUrl(NotificationLogoProxy::class, NotificationsFramework::INJECT_INTO)) . '" width="' . ceil($image_size[0] / 2) . '" height="' . ceil($image_size[1] / 2) . '" alt="' . AngieApplication::getName() . ' logo" style="padding-bottom: 8px;">' . "\n" .
        '<!-- Divider -->' . "\n" .
        '<hr width="100%" color="#000000" size="3">';
    });
}
