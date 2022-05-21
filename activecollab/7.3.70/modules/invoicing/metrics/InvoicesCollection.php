<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use ConfigOptions;
use DateValue;
use DBConnection;
use Integration;
use QuickbooksIntegration;
use XeroIntegration;

class InvoicesCollection extends Collection
{
    private $connection;
    private $integrations;
    private $count_xero_invoices;
    private $count_quickbox_invoices;

    public function __construct(
        DBConnection $connection,
        callable $integrations,
        callable $count_xero_invoices,
        callable $count_quickbox_invoices
    ) {
        $this->connection = $connection;
        $this->integrations = $integrations;
        $this->count_xero_invoices = $count_xero_invoices;
        $this->count_quickbox_invoices = $count_quickbox_invoices;
    }

    public function getValueFor(DateValue $date): ResultInterface
    {
        $until_timestamp = $this->dateToRange($date)[1];
        $backend = $this->getInvoiceBackend();

        [
            $total_invoices,
            $paid_invoices,
            $canceled_invoices,
            $unsent,
            $sent,
            $partially_paid,
        ] = $this->countInvoices($until_timestamp, $backend);

        return $this->produceResult(
            [
                'backend' => $backend,
                'total' => $total_invoices,
                'by_status' => [
                    'paid' => $paid_invoices,
                    'canceled' => $canceled_invoices,
                    'unsent' => $unsent,
                    'sent' => $sent,
                    'partially_paid' => $partially_paid,
                 ],
                'default_item_grouping' => ConfigOptions::getValue('on_invoice_based_on'),
            ],
            $date
        );
    }

    private function countInvoices(string $until_timestamp, string $backend)
    {
        if ($backend === 'xero') {
            return call_user_func($this->count_xero_invoices);
        } elseif ($backend === 'quickbooks') {
            return call_user_func($this->count_quickbox_invoices);
        }

        return $this->countActivecollabInvoices($until_timestamp);
    }

    private function getInvoiceBackend()
    {
       $integrations = [
           XeroIntegration::class,
           QuickbooksIntegration::class,
       ];

        foreach ($integrations as $integration_name) {
            $integration = $this->getIntegration($integration_name);
            if ($integration && $integration->isInUse()) {
                return $integration->getShortName();
            }
       }

       return 'activecollab';
    }

    private function getIntegration($type): ?Integration
    {
        return call_user_func($this->integrations, $type);
    }

    private function countActivecollabInvoices(string $until_timestamp): array
    {
        $total_invoices = 0;
        $paid_invoices = 0;
        $canceled_invoices = 0;
        $unsent = 0;
        $sent = 0;
        $partially_paid = 0;

        $rows = $this->connection->execute(
            "SELECT
                (`closed_on` IS NOT NULL) AS 'is_closed',
                (`sent_on` IS NOT NULL) AS 'is_sent',
                (`paid_amount` < `total` AND `paid_amount` > 0) AS 'is_partially_paid',
                `is_canceled`
                FROM `invoices`
                WHERE `created_on` <= ?",
            [
                $until_timestamp,
            ]
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $total_invoices++;

                if ($row['is_closed']) {
                    if ($row['is_canceled']) {
                        $canceled_invoices++;
                    } else {
                        $paid_invoices++;
                    }
                } else {
                    if ($row['is_partially_paid']) {
                        $partially_paid++;
                    } elseif ($row['is_sent']) {
                        $sent++;
                    } else {
                        $unsent++;
                    }
                }
            }
        }

        return [
            $total_invoices,
            $paid_invoices,
            $canceled_invoices,
            $unsent,
            $sent,
            $partially_paid,
        ];
    }
}
