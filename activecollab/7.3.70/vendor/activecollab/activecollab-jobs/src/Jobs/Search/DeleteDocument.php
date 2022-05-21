<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

use Elastica\Exception\NotFoundException;
use InvalidArgumentException;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
class DeleteDocument extends Job
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

        parent::__construct($data);
    }

    /**
     * Delete document.
     */
    public function execute()
    {
        $index_name = $this->getData()['index'];

        try {
            $this->getType($index_name, $this->getData()['type'])->deleteById($this->getData()['id']);
            $this->getIndex($index_name)->refresh();
        } catch (NotFoundException $e) {
        }
    }
}
