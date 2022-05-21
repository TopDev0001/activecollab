<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use ActiveCollab\JobsQueue\Jobs\Job;
use ActiveCollab\ShepherdSDK\Client;
use ActiveCollab\ShepherdSDK\Token;
use ActiveCollab\ShepherdSDK\Utils\UrlCreator\UrlCreator;
use InvalidArgumentException;

abstract class ShepherdClientJob extends Job
{
    public function __construct(array $data = null)
    {
        if (empty(getenv('SHEPHERD_ACCESS_TOKEN'))) {
            throw new InvalidArgumentException('Shepherd Access Token is required');
        }

        if (empty(getenv('SHEPHERD_URL'))) {
            throw new InvalidArgumentException('Shepherd URL is required');
        }

        if (empty($data['headers'])) {
            $data['headers'] = [];
        }

        if (!array_key_exists('timeout', $data)) {
            $data['timeout'] = 30;
        }

        parent::__construct($data);
    }

    protected function getShepherdClient()
    {
        return new Client(
            new Token(getenv('SHEPHERD_ACCESS_TOKEN')),
            getenv('SHEPHERD_URL')
        );
    }

    protected function getUrlCreator()
    {
        return new UrlCreator(getenv('SHEPHERD_URL'));
    }
}
