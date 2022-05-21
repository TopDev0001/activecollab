<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Wires;

use ActiveCollab\Foundation\App\Proxy\ProxyRequestHandler;

class NotificationLogoProxy extends ProxyRequestHandler
{
    /**
     * Forward image.
     */
    public function execute()
    {
        $file_path = APPLICATION_PATH . '/resources/notification-logo-2x.png';

        if (!is_file($file_path)) {
            $file_path = dirname(__DIR__) . '/resources/notification-logo-2x.png';
        }

        $md5 = md5_file($file_path);

        header('Content-type: image/png');
        header('Cache-Control: public, max-age=315360000');
        header('Pragma: public');
        header('Etag: ' . $md5);

        $cached_hash = $this->getCachedEtag();

        if ($cached_hash && $cached_hash == $md5) {
            $this->notModified();
        }

        require_once ANGIE_PATH . '/functions/general.php';
        require_once ANGIE_PATH . '/functions/web.php';

        download_file($file_path, 'image/png', 'Logo', false, true);
    }
}
