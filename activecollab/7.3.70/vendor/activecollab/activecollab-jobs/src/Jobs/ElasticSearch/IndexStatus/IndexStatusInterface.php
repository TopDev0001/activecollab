<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus;

interface IndexStatusInterface
{
    public function getName();
    public function indexExists();
    public function getCreationTimestamp();
    public function getNumberOfShards();
    public function getNumberOfReplicas();
    public function getDocumentCount();
}
