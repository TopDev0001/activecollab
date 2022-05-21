<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects;

interface RelatedInvoiceProjectsInterface
{
    public function invoiceHasProjects(int $invoice_id): bool;
    public function getRelatedProjects(int $invoice_id): array;
    public function getRelatedProjectNames(int $invoice_id): ?string;
}
