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
class Autocomplete extends Job
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

        $query = new ElasticaQuery($this->getData()['query']);

        if ($records = $this->getIndex($this->getData()['index'])->search($query)) {
            $field_name = $this->getData()['field_name'];
            $also_fetch_fields = $this->getData()['also_fetch_fields'];

            foreach ($records as $record) {
                $data = $record->getData();

                $row = [
                    'class' => $record->getType(),
                    'id' => (integer) $record->getId(),
                    'name' => $data['name'],
                    'url_path' => $data['url'],
                    $field_name => $data[$field_name],
                ];

                foreach ($also_fetch_fields as $also_featch_field) {
                    $row[$also_featch_field] = $data[$also_featch_field];
                }

                $result[] = $row;
            }
        }

        return $result;
    }
}
