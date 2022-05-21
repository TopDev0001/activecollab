<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use InvalidArgumentException;

final class IndexDocument extends Job
{
    public function __construct(array $data = null)
    {
        if (!is_array($data['document_payload']) || empty($data['document_payload'])) {
            throw new InvalidArgumentException('Document body is required.');
        }

        if (empty($data['timestamp'])) {
            $data['timestamp'] = null;
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $params = [
            'index' => $this->getData('index'),
            'type' => $this->getData('type'),
            'id' => $this->getIdInIndex(
                $this->getData('tenant_id'),
                $this->getData('document_id')
            ),
            'body' => $this->getData('document_payload'),
        ];

        $timestamp = $this->getData('timestamp');

        if (!empty($timestamp)) {
            $params['timestamp'] = $timestamp;
        }

        return $this->getClient()->index($params);
    }

    protected function isTenantIdRequired()
    {
        return true;
    }

    protected function isTypeRequired()
    {
        return true;
    }

    protected function isDocumentIdRequired()
    {
        return true;
    }
}
