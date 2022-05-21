<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\InvoicePreSendChecker;

use RecurringProfile;

class InvoicePreSendChecker implements InvoicePreSendCheckerInterface
{
    public function isItSafeToIssueRecurringInvoice(RecurringProfile $recurring_invoice, array $recipients): bool
    {
        return true;
    }
}
