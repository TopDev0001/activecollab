<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

final class DeleteIndex extends Job
{
    public function execute()
    {
        $index_name = $this->getData('index');

        if ($this->indexExists($index_name)) {
            $this->deleteIndex($index_name);
        }
    }
}
