<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\Resolver;

use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\RelatedInvoiceProjectsInterface;

interface RelatedInvoiceProjectsResolverInterface
{
    public function resolveForInvoices(array $invoice_ids): RelatedInvoiceProjectsInterface;
}
