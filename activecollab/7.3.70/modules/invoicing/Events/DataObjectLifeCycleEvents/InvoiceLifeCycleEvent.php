<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Events\DataObjectLifeCycleEvents\InvoiceEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Invoice;

abstract class InvoiceLifeCycleEvent extends DataObjectLifeCycleEvent implements InvoiceLifeCycleEventInterface
{
    public function __construct(Invoice $object)
    {
        parent::__construct($object);
    }
}
