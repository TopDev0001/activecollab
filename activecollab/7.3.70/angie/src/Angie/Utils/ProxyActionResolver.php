<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Utils;

use ActiveCollab\Foundation\Urls\Services\WarehouseUrl;
use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;

class ProxyActionResolver
{
    public function resolveActionPlaceholder(string $proxy_name): string
    {
        switch ($proxy_name) {
            case 'download_attachments_archive':
            case 'download_file':
                return WarehouseUrl::FILE_EXTENSION_DOWNLOAD;
            case 'forward_preview':
                return WarehouseUrl::FILE_EXTENSION_PREVIEW;
            case 'avatar':
                return '';
            case 'invoice_logo':
            case 'forward_thumbnail':
            default:
                return WarehouseUrl::FILE_EXTENSION_THUMBNAILS;
        }
    }

    public function resolveActionIntent(string $proxy_name): string
    {
        switch ($proxy_name) {
            case 'download_attachments_archive':
            case 'download_file':
                return JwtTokenIssuerInterface::INTENT_DOWNLOAD;
            case 'forward_preview':
                return JwtTokenIssuerInterface::INTENT_PREVIEW;
            case 'invoice_logo':
            case 'avatar':
            case 'forward_thumbnail':
            default:
                return JwtTokenIssuerInterface::INTENT_THUMBNAIL;
        }
    }
}
