<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use InvalidArgumentException;

class QueryIndex extends Job
{
    public function __construct(array $data = null)
    {
        if (empty($data['query']) || !is_array($data['query'])) {
            throw new InvalidArgumentException("'query' property is required.");
        }

        if (!array_key_exists('page', $data)) {
            $data['page'] = 1;
        }

        if (!array_key_exists('documents_per_page', $data)) {
            $data['documents_per_page'] = 100;
        }

        if (!is_int($data['page']) || $data['page'] < 1) {
            throw new InvalidArgumentException('Page value is expected to be an integer greater than 0.');
        }

        if (!is_int($data['documents_per_page']) || $data['documents_per_page'] < 1) {
            throw new InvalidArgumentException('Documents per page is expected to be an integer greater than 0.');
        }

        if (empty($data['default_operator'])) {
            $data['default_operator'] = 'OR';
        }

        if (!in_array($data['default_operator'], ['OR', 'AND'])) {
            throw new InvalidArgumentException('Default operator can be OR or AND.');
        }

        if (empty($data['highlight'])) {
            $data['highlight'] = [
                '<em class="highlight">',
                '</em>',
            ];
        }

        if (!is_array($data['highlight']) || count($data['highlight']) != 2) {
            throw new InvalidArgumentException('Highlight settings are expected to be an array of two elements.');
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
        list($offset, $documents_per_page) = $this->getLimit();

        return $this->getClient()->search(
            [
                'index' => $this->getData('index'),
                'type' => $this->getData('type'),
                'body' => [
                    'from' => $offset,
                    'size' => $documents_per_page,
                    'query' => $this->getData('query'),
                    'highlight' => $this->getHighlightSettings(),
                ],
            ]
        );
    }

    private function getLimit()
    {
        $documents_per_page = $this->getData('documents_per_page');

        return [
            ($this->getData('page') - 1) * $documents_per_page,
            $documents_per_page,
        ];
    }

    private function getHighlightSettings()
    {
        $body_highlighter = [
            'type' => 'plain',
            'fragment_size' => 255, // Max length of fragment that is returned.
            'number_of_fragments' => 3, // Max number of fragments that are being returned.
        ];

        return [
            'pre_tags' => [
                $this->getData('highlight')[0],
            ],
            'post_tags' => [
                $this->getData('highlight')[1],
            ],
            'fields' => [
                'name' => [
                    'type' => 'plain',
                    'fragment_size' => 80,
                    'number_of_fragments' => 1,
                ],
                'body' => $body_highlighter,
                'body_extensions' => $body_highlighter,
            ],
        ];
    }

    protected function isTenantIdRequired()
    {
        return true;
    }

    protected function isTypeRequired()
    {
        return true;
    }
}
