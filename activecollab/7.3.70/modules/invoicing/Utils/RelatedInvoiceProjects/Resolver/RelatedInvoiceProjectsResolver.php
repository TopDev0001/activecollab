<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\Resolver;

use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\RelatedInvoiceProjects;
use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\RelatedInvoiceProjectsInterface;
use DB;
use Invoice;
use Project;
use Projects;
use Task;

class RelatedInvoiceProjectsResolver implements RelatedInvoiceProjectsResolverInterface
{
    public function resolveForInvoices(array $invoice_ids): RelatedInvoiceProjectsInterface
    {
        $projects_from_tracking_records = [];

        $this->getDataFromInvoiceItems($projects_from_tracking_records, $invoice_ids);
        $this->getDataFromTrackingItems(
            $projects_from_tracking_records,
            $invoice_ids,
            'time_records'
        );
        $this->getDataFromTrackingItems(
            $projects_from_tracking_records,
            $invoice_ids,
            'expenses'
        );

        $project_ids = array_keys($projects_from_tracking_records);

        $result = [];

        if (!empty($project_ids)) {
            $rows = DB::execute('SELECT `id`, `name` FROM `projects` WHERE `id` IN (?)', $project_ids);

            if ($rows) {
                foreach ($rows as $row) {
                    $project_id = $row['id'];
                    $project_invoice_ids = $projects_from_tracking_records[$project_id];

                    foreach ($project_invoice_ids as $project_invoice_id) {
                        if (isset($result[$project_invoice_id][$project_id])) {
                            continue;
                        }

                        $result[$project_invoice_id][$project_id] = [
                            'id' => $project_id,
                            'name' => $row['name'],
                        ];
                    }
                }

                unset($projects_from_tracking_records);
            }
        }

        $result = $this->populateDirectlyLinkedProjectNames($result, $invoice_ids);

        return new RelatedInvoiceProjects($result);
    }

    private function populateDirectlyLinkedProjectNames(array $result, array $invoice_ids): array
    {
        $invoice_projects = DB::execute(
            'SELECT `id`, `project_id` FROM `invoices` WHERE `id` IN (?) AND `project_id` > ?',
            $invoice_ids,
            0
        );

        if (!empty($invoice_projects)) {
            $missing_project_name_ids = [];

            foreach ($invoice_projects as $invoice_project) {
                if (empty($result[$invoice_project['id']][$invoice_project['project_id']])) {
                    $missing_project_name_ids[] = $invoice_project['project_id'];
                }
            }

            $project_names = Projects::getIdNameMap($missing_project_name_ids);

            if (!empty($project_names)) {
                foreach ($invoice_projects as $invoice_project) {
                    $invoice_id = $invoice_project['id'];
                    $project_id = $invoice_project['project_id'];

                    if (empty($result[$invoice_id][$project_id]) && !empty($project_names[$project_id])) {
                        $result[$invoice_id][$project_id] = [
                            'id' => $project_id,
                            'name' => $project_names[$project_id],
                        ];
                    }
                }
            }
        }

        return $result;
    }

    private function getDataFromInvoiceItems(
        array &$invoice_projects,
        array $invoice_ids
    ): void
    {
        $rows = DB::execute(
            'SELECT `parent_id`, `project_id` FROM `invoice_items` WHERE `parent_type` = ? AND `parent_id` IN (?)',
            Invoice::class,
            $invoice_ids
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $invoice_id = $row['parent_id'];
                $project_id = $row['project_id'];

                if (!isset($invoice_projects[$project_id])) {
                    $invoice_projects[$project_id] = [];
                }

                if (!in_array($invoice_id, $invoice_projects[$project_id])) {
                    $invoice_projects[$project_id][] = $invoice_id;
                }
            }
        }
    }

    private function getDataFromTrackingItems(
        array &$invoice_projects,
        array $invoice_ids,
        string $table
    ): void
    {
        $sql = "SELECT ii.parent_id as invoice_id, 
            tr.parent_type as type, 
            tr.parent_id as item_id, 
            t.project_id as task_project_id 
            FROM invoice_items ii
            JOIN {$table} tr ON ii.id = tr.invoice_item_id AND invoice_type = ?
            LEFT JOIN tasks t ON tr.parent_type = ? AND t.id = tr.parent_id 
            WHERE ii.parent_id in (?) AND ii.parent_type = ?
            GROUP BY ii.parent_id, tr.parent_type, tr.parent_id, t.project_id";

        $rows = DB::execute($sql, Invoice::INVOICE_TYPE, Task::class, $invoice_ids, Invoice::class);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $invoice_id = $row['invoice_id'];
                $project_id = $row['type'] == Project::class
                    ? $row['item_id']
                    : $row['task_project_id'];

                if (!isset($invoice_projects[$project_id])) {
                    $invoice_projects[$project_id] = [];
                }

                if (!in_array($invoice_id, $invoice_projects[$project_id])) {
                    $invoice_projects[$project_id][] = $invoice_id;
                }
            }
        }
    }
}
