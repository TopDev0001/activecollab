<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs\Http;

use ActiveCollab\ActiveCollabJobs\Jobs\Job;
use GuzzleHttp\Client;
use InvalidArgumentException;

class SendRequest extends Job
{
    const DEFAULT_METHOD = 'POST';
    const DEFAULT_VERIFY = true;
    const DEFAULT_TIMEOUT = 30;

    public function __construct(array $data)
    {
        if (empty($data['method'])) {
            $data['method'] = self::DEFAULT_METHOD;
        }

        $data['method'] = strtoupper($data['method']);

        if (!isset($data['verify']) || !is_bool($data['verify'])) {
            $data['verify'] = self::DEFAULT_VERIFY;
        }

        if (empty($data['url']) || !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Valid URL is required');
        }

        if (!array_key_exists('payload', $data) || (empty($data['payload']) && !is_array($data['payload']))) {
            if ($this->requiresPayload($data['method'])) {
                throw new InvalidArgumentException('Payload is required');
            }

            $data['payload'] = '';
        }

        if (empty($data['headers'])) {
            $data['headers'] = [];
        }

        if (!array_key_exists('timeout', $data)) {
            $data['timeout'] = self::DEFAULT_TIMEOUT;
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $client = new Client();

        $headers = $this->getData('headers');
        $headers['User-Agent'] = 'Active Collab';

        $request_options = [
            'headers' => $headers,
            'timeout' => $this->getData('timeout'),
            'verify' => (bool) $this->getData('verify'),
        ];

        if ($this->requiresPayload($this->getData('method')) && $this->getData('payload')) {
            $request_options['body'] = $this->getData('payload');
        }

        $client->request(
            $this->getData('method'),
            $this->getData('url'),
            $request_options
        );
    }

    private function requiresPayload(string $method): bool
    {
        return in_array($method, ['POST', 'PUT']);
    }
}
