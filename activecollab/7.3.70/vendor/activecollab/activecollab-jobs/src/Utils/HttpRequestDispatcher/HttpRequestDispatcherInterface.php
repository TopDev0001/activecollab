<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\HttpRequestDispatcher;

use Psr\Http\Message\ResponseInterface;

interface HttpRequestDispatcherInterface
{
    public function postJson(
        string $url,
        array $headers,
        string $json_payload,
        int $timeout = 600,
        bool $verify_peer = false
    ): ResponseInterface;
}
