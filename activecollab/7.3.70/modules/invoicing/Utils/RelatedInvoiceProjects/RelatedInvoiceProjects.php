<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects;

class RelatedInvoiceProjects implements RelatedInvoiceProjectsInterface
{
    private array $invoice_projects_data;

    public function __construct(array $invoice_projects_data)
    {
        $this->invoice_projects_data = $invoice_projects_data;
    }

    public function invoiceHasProjects(int $invoice_id): bool
    {
        return !empty($this->invoice_projects_data[$invoice_id]);
    }

    public function getRelatedProjects(int $invoice_id): array
    {
        $result = [];

        if ($this->invoiceHasProjects($invoice_id)) {
            foreach ($this->invoice_projects_data[$invoice_id] as $invoice_project) {
                $result[] = [
                    $invoice_project['id'] => $invoice_project['name'],
                ];
            }
        }

        return $result;
    }

    public function getRelatedProjectNames(int $invoice_id): ?string
    {
        if (!$this->invoiceHasProjects($invoice_id)) {
            return null;
        }

        $project_names = [];

        foreach ($this->invoice_projects_data[$invoice_id] as $invoice_project) {
            $project_names[] = $invoice_project['name'];
        }

        return implode(', ', $project_names);
    }
}
