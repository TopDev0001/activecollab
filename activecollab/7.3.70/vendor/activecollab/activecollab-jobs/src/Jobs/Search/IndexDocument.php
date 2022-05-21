<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

use Elastica\Document;
use Elastica\Exception\ResponseException;
use InvalidArgumentException;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
class IndexDocument extends Job
{
    /**
     * Construct a new Job instance.
     *
     * @param  array|null               $data
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = null)
    {
        if (empty($data['type'])) {
            throw new InvalidArgumentException("'type' property is required");
        }

        if (empty($data['id']) || $data['id'] < 1) {
            throw new InvalidArgumentException('Valid ID is required');
        }

        if (empty($data['fields']) || !is_array($data['fields'])) {
            throw new InvalidArgumentException('An array of document fields is required');
        }

        parent::__construct($data);
    }

    /**
     * Index document.
     */
    public function execute()
    {
        $index_name = $this->getData()['index'];

        $type = $this->getType($index_name, $this->getData()['type']);
        $document_to_index = new Document($this->getData()['id'], $this->getData()['fields']);

        try {
            $type->updateDocument($document_to_index);
        } catch (ResponseException $e) {
            if (strpos($e->getMessage(), 'DocumentMissingException') !== false) {
                $type->addDocument($document_to_index);
            } else {
                throw new $e();
            }
        }

        $this->getIndex($index_name)->refresh();
    }
}
