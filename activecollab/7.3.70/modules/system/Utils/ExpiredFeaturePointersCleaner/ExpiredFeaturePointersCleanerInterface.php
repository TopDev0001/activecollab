<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\ExpiredFeaturePointersCleaner;

interface ExpiredFeaturePointersCleanerInterface
{
    public function cleanExpiredFeaturePointers(): void;
}
