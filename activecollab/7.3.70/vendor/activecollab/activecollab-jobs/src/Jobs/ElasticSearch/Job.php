<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use ActiveCollab\ActiveCollabJobs\Jobs\Job as BaseJob;
use ActiveCollab\JobsQueue\Helpers\Port;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use InvalidArgumentException;

abstract class Job extends BaseJob
{
    use Port;

    /**
     * Construct a new Job instance.
     *
     * @param  array|null               $data
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = null)
    {
        if ($data === null) {
            $data = [];
        }

        if (array_key_exists('hosts', $data)) {
            if (!is_array($data['hosts']) || empty($data['hosts'])) {
                throw new InvalidArgumentException('A valid list of hosts is required.');
            }
        } else {
            if (empty($data['host'])) {
                throw new InvalidArgumentException("Single 'host' or a list of 'hosts' is required.");
            }

            $this->validatePort($data, 9200);

            $data['hosts'] = ["{$data['host']}:{$data['port']}"];

            unset($data['host']);
            unset($data['port']);
        }

        if ($this->isIndexRequired() && empty($data['index'])) {
            throw new InvalidArgumentException("Valid 'index' value is required.");
        }

        if ($this->isTenantIdRequired() && (empty($data['tenant_id']) || !$this->isValidTenantId($data['tenant_id']))) {
            throw new InvalidArgumentException("Valid 'tenant_id' value is required.");
        }

        if ($this->isTypeRequired() && empty($data['type'])) {
            throw new InvalidArgumentException("Valid 'type' value is required.");
        }

        if ($this->isDocumentIdRequired()
            && (empty($data['document_id']) || !$this->isValidDocumentId($data['document_id']))
        ) {
            throw new InvalidArgumentException("Valid 'document_id' value is required.");
        }

        parent::__construct($data);
    }

    /**
     * Return true if index property is required.
     *
     * @return bool
     */
    protected function isIndexRequired()
    {
        return true;
    }

    /**
     * Return true if tenant_id property is required.
     *
     * @return bool
     */
    protected function isTenantIdRequired()
    {
        return false;
    }

    /**
     * Return true if type attribute is required.
     */
    protected function isTypeRequired()
    {
        return false;
    }

    /**
     * Return true if id property is required for this job to be performed.
     *
     * @return bool
     */
    protected function isDocumentIdRequired()
    {
        return false;
    }

    /**
     * Return true if $tenent_id value is a valid tenant ID.
     *
     * @param  mixed $tenant_id
     * @return bool
     */
    private function isValidTenantId($tenant_id)
    {
        return is_int($tenant_id) && $tenant_id > 0;
    }

    /**
     * Return true if $document_id value is a valid document ID.
     *
     * @param  mixed $document_id
     * @return bool
     */
    private function isValidDocumentId($document_id)
    {
        return is_string($document_id) && !empty($document_id);
    }

    /**
     * Return ElasticSearch client.
     *
     * @var Client
     */
    private $client = false;

    /**
     * Return Elastica client instance.
     *
     * @return Client
     */
    protected function &getClient()
    {
        if ($this->client === false) {
            $this->client = (new ClientBuilder())
                ->setHosts($this->getData('hosts'))
                ->setLogger($this->log)
                ->build();
        }

        return $this->client;
    }

    /**
     * Return true if index $index_name exists.
     *
     * @param  string $index_name
     * @return bool
     */
    protected function indexExists($index_name)
    {
        try {
            $status = $this->getClient()->indices()->get(
                [
                    'index' => $index_name,
                ]
            );

            return is_array($status)
                && !empty($status[$index_name]['settings']['index'])
                && array_key_exists('creation_date', $status[$index_name]['settings']['index']);
        } catch (Missing404Exception $e) {
            return false;
        }
    }

    /**
     * Delete the index.
     *
     * Note: This method does not check for index existance!
     *
     * @param string $index_name
     */
    protected function deleteIndex($index_name)
    {
        $this->getClient()->indices()->delete(
            [
                'index' => $index_name,
            ]
        );
    }

    /**
     * Return ID that should be looked for in the index.
     *
     * @param  int    $tenant_id
     * @param  string $document_id
     * @return string
     */
    protected function getIdInIndex($tenant_id, $document_id)
    {
        return "tenants-{$tenant_id}-$document_id";
    }
}
