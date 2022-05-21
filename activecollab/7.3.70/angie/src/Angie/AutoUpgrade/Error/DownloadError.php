<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\AutoUpgrade\Error;

use Angie\Error;

/**
 * @package Angie\Search\Error
 */
class DownloadError extends Error
{
    /**
     * @param string $download_url
     * @param null   $message
     */
    public function __construct($download_url, $message = null)
    {
        if (empty($message)) {
            $message = "Failed to download new release from '$download_url'";
        }

        parent::__construct($message, ['download_url' => $download_url]);
    }
}
