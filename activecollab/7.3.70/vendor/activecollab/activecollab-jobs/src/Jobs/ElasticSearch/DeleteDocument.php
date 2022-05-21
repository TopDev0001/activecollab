<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use Exception;

final class DeleteDocument extends Job
{
    public function execute()
    {
        try {
            return $this->getClient()->delete(
                [
                    'index' => $this->getData('index'),
                    'type' => $this->getData('type'),
                    'id' => $this->getIdInIndex(
                        $this->getData('tenant_id'),
                        $this->getData('document_id')
                    ),
                ]
            );
        } catch (Exception $e) {
            if ($this->log) {
                $this->log->error(
                    'Delete search document job failed.',
                    [
                        'message' => $e->getMessage(),
                        'index' => $this->getData('index'),
                        'id' => $this->getIdInIndex(
                            $this->getData('tenant_id'),
                            $this->getData('document_id')
                        ),
                    ]
                );
            }

            throw $e;
        }
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
