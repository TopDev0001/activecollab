<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

use Exception;
use InvalidArgumentException;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
class CreateIndex extends Job
{
    /**
     * Construct a new Job instance.
     *
     * @param  array|null               $data
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = null)
    {
        if (!array_key_exists('number_of_shards', $data)) {
            $data['number_of_shards'] = 4;
        }

        if (!array_key_exists('number_of_replicas', $data)) {
            $data['number_of_replicas'] = 1;
        }

        foreach (['number_of_shards', 'number_of_replicas'] as $check) {
            if ($data[$check] < 0) {
                throw new InvalidArgumentException("'$check' value is not valid");
            }
        }

        parent::__construct($data);
    }

    /**
     * Create a new search index.
     *
     * @return array
     */
    public function execute()
    {
        $index = $this->getIndex($this->getData()['index'], false);

        if ($index->exists()) {
            try {
                $index->delete();
            } catch (Exception $e) {
            }
        }

        return $index->create([
            'number_of_shards' => $this->getData()['number_of_shards'],
            'number_of_replicas' => $this->getData()['number_of_replicas'],
            'analysis' => [
                'filter' => [
                    'shingle_filter' => [
                        'type' => 'shingle',
                        'min_shingle_size' => 2,
                        'max_shingle_size' => 5,
                    ],
                ],
                'analyzer' => [
                    'shingle_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'shingle_filter'],
                    ],
                ],
            ],
        ]);
    }
}
