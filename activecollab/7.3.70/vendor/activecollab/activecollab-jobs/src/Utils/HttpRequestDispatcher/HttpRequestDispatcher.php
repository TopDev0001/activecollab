<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\HttpRequestDispatcher;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class HttpRequestDispatcher implements HttpRequestDispatcherInterface
{
    public function postJson(
        string $url,
        array $headers,
        string $json_payload,
        int $timeout = 600,
        bool $verify_peer = false
    ): ResponseInterface {
        $client = new Client();

        $headers['User-Agent'] = 'Active Collab';

        return $client->post(
            $url,
            [
                RequestOptions::HEADERS => $headers,
                RequestOptions::CONNECT_TIMEOUT => $timeout,
                RequestOptions::TIMEOUT => $timeout,
                RequestOptions::VERIFY => $verify_peer,
                RequestOptions::BODY => $json_payload,
                RequestOptions::HTTP_ERRORS => false,
            ]
        );
    }
}
