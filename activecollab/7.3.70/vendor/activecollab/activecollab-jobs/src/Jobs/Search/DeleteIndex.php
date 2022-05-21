<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
class DeleteIndex extends Job
{
    /**
     * Drop the index.
     */
    public function execute()
    {
        $index = $this->getIndex($this->getData()['index'], false);

        if ($index->exists()) {
            $index->delete();
        }
    }
}
