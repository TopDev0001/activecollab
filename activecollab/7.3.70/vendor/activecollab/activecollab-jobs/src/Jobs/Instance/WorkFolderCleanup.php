<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class WorkFolderCleanup extends Job
{
    /**
     * Clean up work folder for the given instance.
     */
    public function execute()
    {
        // @TODO One day maybe when we decide to clean up multi-account
        return true;
    }
}
