<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\Proxy\UrlBuilder;

interface ProxyUrlBuilderInterface
{
    public function buildUrl(
        string $proxy_class,
        string $module_name,
        array $params = []
    ): string;

    public function getInvalidationHash(string $proxy_class): string;
}
