<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use InvalidArgumentException;
use LogicException;

final class CreateIndex extends Job
{
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

        if (empty($data['document_type'])) {
            $data['document_type'] = '';
        }

        if (empty($data['document_mapping'])) {
            $data['document_mapping'] = [];
        }

        if (!empty($data['document_mapping']) && empty($data['document_type'])) {
            throw new LogicException('Document mapping is set, but document type is not.');
        }

        if (!is_array($data['document_mapping'])) {
            throw new InvalidArgumentException('Document mapping is expected to be an array.');
        }

        $data['force'] = !empty($data['force']);

        parent::__construct($data);
    }

    public function execute()
    {
        $index_name = $this->getData('index');

        if ($this->indexExists($index_name)) {
            if ($this->getData('force')) {
                if ($this->log) {
                    $this->log->info(
                        'Index {index} found. Deleting it, because we are in force create mode.',
                        [
                            'index' => $index_name,
                        ]
                    );
                }

                $this->deleteIndex($index_name);
            } else {
                if ($this->log) {
                    $this->log->info(
                        'Index {index} found. Skipping index creation.',
                        [
                            'index' => $index_name,
                        ]
                    );
                }

                return null;
            }
        }

        $body = [
            'settings' => $this->getIndexSettings(),
        ];

        $mappings = $this->getMappings();

        if (!empty($mappings)) {
            $body['mappings'] = $mappings;
        }

        return $this->getClient()->indices()->create(
            [
                'index' => $index_name,
                'body' => $body,
            ]
        );
    }

    private function getIndexSettings()
    {
        return [
            'number_of_shards' => $this->getData('number_of_shards'),
            'number_of_replicas' => $this->getData('number_of_replicas'),
        ];
    }

    private function getMappings()
    {
        $mappings = [];

        $document_type = $this->getData('document_type');
        $document_mapping = $this->getData('document_mapping');

        if (!empty($document_type) && !empty($document_mapping)) {
            $mappings[$document_type] = $document_mapping;
        }

        return $mappings;
    }
}
