<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\RootUrl;

use ActiveCollab\Foundation\Urls\UrlInterface;

interface RootUrlInterface extends UrlInterface
{
    public function isInternalUrl(string $url_to_check): bool;
    public function expandRelativeUrl(string $from_relative_url): string;
    public function getRelativeUrl(string $url): ?string;
}
