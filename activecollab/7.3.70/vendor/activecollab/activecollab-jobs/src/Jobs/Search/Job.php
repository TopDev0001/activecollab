<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

use ActiveCollab\ActiveCollabJobs\Jobs\Job as BaseJob;
use ActiveCollab\JobsQueue\Helpers\Port;
use Elastica\Client;
use Elastica\Index;
use Elastica\Type;
use InvalidArgumentException;
use RuntimeException;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
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
        if (empty($data['host'])) {
            throw new InvalidArgumentException("'host' property is required");
        }

        $this->validatePort($data, 9200);

        if ($this->indexIsRequired() && empty($data['index'])) {
            throw new InvalidArgumentException("'index' property is required");
        }

        parent::__construct($data);
    }

    /**
     * Return true if index property is required.
     *
     * @return bool
     */
    protected function indexIsRequired()
    {
        return true;
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
            $this->client = new Client([
                'host' => $this->getData()['host'],
                'port' => $this->getData()['port'],
            ]);
        }

        return $this->client;
    }

    /**
     * Main elasict search index for this application.
     *
     * @var Index[]
     */
    private $indexes = [];

    /**
     * Return ElasticSearch index.
     *
     * @param  string $index_name
     * @param  bool   $it_should_exist
     * @return Index
     */
    protected function &getIndex($index_name, $it_should_exist = true)
    {
        if (empty($this->indexes[$index_name])) {
            $index = $this->getClient()->getIndex($index_name);

            if ($it_should_exist && !$index->exists()) {
                throw new RuntimeException("Search index '$index_name' does not exist");
            }

            $this->indexes[$index_name] = $index;
        }

        return $this->indexes[$index_name];
    }

    /**
     * @param  string $index_name
     * @param  string $type
     * @return Type
     */
    protected function getType($index_name, $type)
    {
        return $this->getIndex($index_name)->getType($type);
    }

    /**
     * Return true if mapping for $type exists.
     *
     * @param  string $index_name
     * @param  string $type
     * @return bool
     */
    protected function mappingExists($index_name, $type)
    {
        return array_key_exists($type, $this->getIndex($index_name)->getMapping());
    }
}
