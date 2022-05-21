<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class ProjectsInvoicingDataCollection extends CompositeCollection
{
    /**
     * @var string
     */
    private $tag;

    public function execute()
    {
        $time_expenses_projects = $this->getTimeExpensesProjects();
        $fixed_priced_projects = $this->getFixedPriceProjects();

        return [
            'time_expenses_projects' => $time_expenses_projects,
            'fixed_priced_projects' => $fixed_priced_projects,
        ];
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        $time_expenses_projects = $this->getTimeExpensesProjects();
        $fixed_priced_projects = $this->getFixedPriceProjects();

        return count($time_expenses_projects) + count($fixed_priced_projects);
    }

    public function getModelName(): string
    {
        return Projects::class;
    }

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if (!$this->tag || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits(
                $user->getEmail(),
                sha1($this->getMaxProjectsUpdatedOn() . '-' . $this->getMaxInvoicesUpdatedOn() . '-' . $this->getMaxTrackingItems())
            );
        }

        return $this->tag;
    }

    private function getTimeExpensesProjects()
    {
        $time_records_on_projects_query = '
            SELECT p.id, p.name, p.completed_on
            FROM projects AS p
             INNER JOIN time_records AS tr ON tr.parent_type = "Project" AND tr.parent_id = p.id
            WHERE p.budget_type = "pay_as_you_go" AND p.is_trashed = 0 AND p.is_sample = 0 AND tr.billable_status = 1 AND tr.is_trashed = 0 AND p.is_tracking_enabled = 1 AND (p.completed_on IS NULL OR (p.completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 6 MONTH) AND NOW()))
            GROUP BY p.id';

        $expenses_on_projects_query = '
            SELECT p.id, p.name, p.completed_on
            FROM projects AS p
             INNER JOIN expenses AS e ON e.parent_type = "Project" AND e.parent_id = p.id
            WHERE p.budget_type = "pay_as_you_go" AND p.is_trashed = 0 AND p.is_sample = 0 AND e.billable_status = 1 AND e.is_trashed = 0 AND p.is_tracking_enabled = 1 AND (p.completed_on IS NULL OR (p.completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 6 MONTH) AND NOW()))
            GROUP BY p.id';

        $time_records_on_tasks_query = '
            SELECT p.id, p.name, p.completed_on
            FROM tasks AS t
             INNER JOIN projects AS p ON p.id = t.project_id
             INNER JOIN time_records AS tr ON tr.parent_type = "Task" AND tr.parent_id = t.id
            WHERE p.budget_type = "pay_as_you_go" AND p.is_trashed = 0 AND p.is_sample = 0 AND tr.billable_status = 1 AND tr.is_trashed = 0 AND p.is_tracking_enabled = 1 AND (p.completed_on IS NULL OR (p.completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 6 MONTH) AND NOW()))
            GROUP BY p.id';

        $expenses_on_tasks_query = '
            SELECT p.id, p.name, p.completed_on
            FROM tasks AS t
             INNER JOIN projects AS p ON p.id = t.project_id
             INNER JOIN expenses AS e ON e.parent_type = "Task" AND e.parent_id = t.id
            WHERE p.budget_type = "pay_as_you_go" AND p.is_trashed = 0 AND p.is_sample = 0 AND e.billable_status = 1 AND e.is_trashed = 0 AND p.is_tracking_enabled = 1 AND (p.completed_on IS NULL OR (p.completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 6 MONTH) AND NOW()))
            GROUP BY p.id';

        $projects = [];
        foreach ([$time_records_on_projects_query, $time_records_on_tasks_query, $expenses_on_projects_query, $expenses_on_tasks_query] as $query) {
            $result = DB::execute($query);
            if ($result) {
                $projects = array_replace($projects, $result->toArrayIndexedBy('id'));
            }
        }

        return array_values($projects);
    }

    private function getFixedPriceProjects()
    {
        $query = "
            SELECT p.id, p.name, p.budget AS left_to_invoice, p.completed_on
            FROM projects AS p
            WHERE p.budget_type = 'fixed' AND p.is_trashed = 0 AND p.is_sample = 0 AND p.is_tracking_enabled = 1 AND (p.completed_on IS NULL OR (p.completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 6 MONTH) AND NOW()))";
        $result = DB::execute($query);

        if (!$result) {
            return [];
        } else {
            $projects = $result->toArrayIndexedBy('id');
        }

        $query = "
            SELECT p.id, SUM(ii.subtotal) AS invoiced, p.completed_on
            FROM projects AS p
             INNER JOIN invoice_items AS ii ON ii.project_id = p.id
             INNER JOIN invoices AS i ON ii.parent_id = i.id AND ii.parent_type = 'Invoice'
            WHERE p.budget_type = 'fixed' AND i.is_trashed != 1 AND i.is_canceled != 1 AND p.is_tracking_enabled = 1 AND (p.completed_on IS NULL OR (p.completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 6 MONTH) AND NOW()))
            GROUP BY p.id";
        $result = DB::execute($query);

        $already_invoiced = [];

        if ($result) {
            foreach ($result->toMap('id', 'invoiced') as $id => $invoiced) {
                $already_invoiced[$id] = $invoiced;
            }
        }

        // check for remote invoicing
        $query = "
            SELECT p.id, SUM(rii.amount) AS invoiced
            FROM projects AS p
             INNER JOIN remote_invoice_items AS rii ON rii.project_id = p.id
            WHERE p.budget_type = 'fixed'
            GROUP BY p.id
        ";
        $result = DB::execute($query);

        if ($result) {
            foreach ($result->toMap('id', 'invoiced') as $id => $invoiced) {
                if (array_key_exists($id, $already_invoiced)) {
                    $already_invoiced[$id] += $invoiced;
                } else {
                    $already_invoiced[$id] = $invoiced;
                }
            }
        }

        foreach ($already_invoiced as $id => $invoiced) {
            $budget = $projects[$id]['left_to_invoice'];
            if ($invoiced >= $budget) {
                unset($projects[$id]);
            } else {
                $projects[$id]['left_to_invoice'] = $budget - $invoiced;
            }
        }

        return array_values($projects);
    }

    private function getMaxProjectsUpdatedOn()
    {
        $query = 'SELECT MAX(updated_on) FROM projects';

        return DB::executeFirstCell($query);
    }

    private function getMaxInvoicesUpdatedOn()
    {
        $query_local = 'SELECT MAX(updated_on) FROM invoices';
        $query_remote = 'SELECT MAX(updated_on) FROM remote_invoice_items';

        $local_max = DB::executeFirstCell($query_local);
        $remote_max = DB::executeFirstCell($query_remote);

        return $local_max . $remote_max;
    }

    private function getMaxTrackingItems()
    {
        $query_tr = 'SELECT MAX(updated_on) FROM time_records';
        $query_expenses = 'SELECT MAX(updated_on) FROM expenses';

        $tr_max = DB::executeFirstCell($query_tr);
        $expenses_max = DB::executeFirstCell($query_expenses);

        return $tr_max . $expenses_max;
    }
}
