<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus;

use LogicException;

final class IndexStatus implements IndexStatusInterface
{
    private $name;
    private $index_exists;
    private $creation_timestamp;
    private $number_of_shards;
    private $number_of_replicas;
    private $document_count;

    /**
     * @param string $name
     * @param bool $index_exists
     * @param int $creation_timestamp
     * @param int $number_of_shards
     * @param int $number_of_replicas
     * @param int $document_count
     */
    public function __construct(
        $name,
        $index_exists,
        $creation_timestamp = 0,
        $number_of_shards = 0,
        $number_of_replicas = 0,
        $document_count = 0
    ) {
        $this->name = $name;
        $this->index_exists = $index_exists;

        if ($this->index_exists && empty($creation_timestamp)) {
            throw new LogicException('Creation timestamp is required for existing indexes.');
        }

        $this->creation_timestamp = $creation_timestamp;
        $this->number_of_shards = $number_of_shards;
        $this->number_of_replicas = $number_of_replicas;
        $this->document_count = $document_count;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function indexExists()
    {
        return $this->index_exists;
    }

    /**
     * @return int
     */
    public function getCreationTimestamp()
    {
        return $this->creation_timestamp;
    }

    /**
     * @return int
     */
    public function getNumberOfShards()
    {
        return $this->number_of_shards;
    }

    /**
     * @return int
     */
    public function getNumberOfReplicas()
    {
        return $this->number_of_replicas;
    }

    public function getDocumentCount()
    {
        return $this->document_count;
    }
}
