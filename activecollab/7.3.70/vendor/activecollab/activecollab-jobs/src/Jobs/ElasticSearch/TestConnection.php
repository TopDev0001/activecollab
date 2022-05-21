<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\TestConnectionStatus\TestConnectionStatus;
use Exception;

final class TestConnection extends Job
{
    public function __construct($data = null)
    {
        foreach (['min_version', 'successful_connection_message', 'min_version_not_met', 'unexpected_response'] as $optional_property) {
            if (empty($data[$optional_property])) {
                $data[$optional_property] = '';
            }
        }

        parent::__construct($data);
    }

    public function execute()
    {
        try {
            return $this->prepareStatusResponse($this->getClient()->info());
        } catch (Exception $e) {
            return $this->prepareStatusResponse($e);
        }
    }

    private function prepareStatusResponse($response)
    {
        $min_version = $this->getData('min_version');
        $successful_connection_message = $this->getData('successful_connection_message');
        $min_version_not_met = $this->getData('min_version_not_met');
        $unexpected_response = $this->getData('unexpected_response');

        if ($successful_connection_message && $min_version_not_met && $unexpected_response) {
            return new TestConnectionStatus(
                $response,
                $min_version,
                $successful_connection_message,
                $min_version_not_met,
                $unexpected_response
            );
        } else {
            return new TestConnectionStatus($response, $min_version);
        }
    }

    protected function isIndexRequired()
    {
        return false;
    }
}
