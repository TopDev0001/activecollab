<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use InvalidArgumentException;

final class DeleteDocuments extends Job
{
    /**
     * {@inheritdoc}
     */
    public function __construct($data = null)
    {
        if (empty($data['body']) || !is_array($data['body'])) {
            throw new InvalidArgumentException("'body' property is required.");
        }

        parent::__construct($data);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->getClient()->deleteByQuery(
            [
                'index' => $this->getData('index'),
                'type' => $this->getData('type'),
                'body' => $this->getData('body'),
            ]
        );
    }
}
