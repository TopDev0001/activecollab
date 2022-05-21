<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

use Exception;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
class TestConnection extends Job
{
    /**
     * Execute the job.
     *
     * @return bool|string
     */
    public function execute()
    {
        try {
            $this->getClient()->getStatus();

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Return true if index property is required.
     *
     * @return bool
     */
    protected function indexIsRequired()
    {
        return false;
    }
}
