<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Files\Events\DataObjectLifeCycleEvents\FileEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use File;

abstract class FileLifecycleEvent extends DataObjectLifeCycleEvent implements FileLifecycleEventInterface
{
    public function __construct(File $object)
    {
        parent::__construct($object);
    }
}
