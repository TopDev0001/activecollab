<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\RecurringInvoicesDispatcher;

use DateValue;
use Invoice;

interface RecurringInvoicesDispatcherInterface
{
    /**
     * Trigger profiles that are due on $day and issue invoices.
     *
     * @return iterable|Invoice[]
     */
    public function trigger(DateValue $day): iterable;
}
