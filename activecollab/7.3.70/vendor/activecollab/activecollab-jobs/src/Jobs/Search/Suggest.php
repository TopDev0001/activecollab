<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

use Elastica\Query as ElasticaQuery;
use InvalidArgumentException;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
class Suggest extends Job
{
    /**
     * Construct a new Job instance.
     *
     * @param  array|null               $data
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = null)
    {
        if (empty($data['query']) || !is_array($data['query'])) {
            throw new InvalidArgumentException("'query' property is required");
        }

        parent::__construct($data);
    }

    /**
     * Query search index with the given parameters.
     *
     * @return array
     */
    public function execute()
    {
        $result = [];

        $query = new ElasticaQuery(['query' => $this->getData()['query']]);

        if ($records = $this->getIndex($this->getData()['index'])->search($query)) {
            $existing_names = [];

            foreach ($records as $record) {
                $data = $record->getData();

                if (in_array($data['name'], $existing_names)) {
                    continue;
                }

                $existing_names[] = $data['name'];

                if (empty($result[$data['name']])) {
                    $result[] = ['class' => $record->getType(), 'id' => (integer) $record->getId(), 'name' => $data['name'], 'url_path' => $data['url']];
                }
            }
        }

        return $result;
    }
}
