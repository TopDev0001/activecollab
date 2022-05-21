<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\TestConnectionStatus;

interface TestConnectionStatusInterface
{
    /**
     * @return bool
     */
    public function isSuccess();

    /**
     * @return string
     */
    public function getClusterInfo();

    /**
     * @return string
     */
    public function getFailureReason();
}
