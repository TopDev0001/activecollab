<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TrashEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use DataObject;
use ITrash;

abstract class TrashEvent extends DataObjectLifeCycleEvent implements TrashEventInterface
{
    /**
     * @param DataObject|ITrash $object
     */
    public function __construct(ITrash $object)
    {
        parent::__construct($object);
    }

    protected function prefixWebhookEventType(string $sufix): string
    {
        return sprintf('%s%s', get_class($this->getObject()), $sufix);
    }
}
