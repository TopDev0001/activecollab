<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use DB;
use ITrackingObject;

class TimeRecordsCollection extends Collection
{
    public function getValueFor(DateValue $date): ResultInterface
    {
        $not_billable = 0;
        $billable = 0;
        $to_be_invoiced = 0;
        $pending_payment = 0;
        $paid = 0;
        $invoiced = DB::executeFirstCell(
            'SELECT COUNT(`id`) AS "row_count" FROM `time_records` WHERE `invoice_item_id` > ?',
            0
        );
        $summary_length = [];
        $source = [];

        $rows = DB::execute(
            'SELECT
                COUNT(`id`) AS "row_count",
                `summary_length`,
                `source`,
                `billable_status`
            FROM `time_records`
            WHERE `is_trashed` = ?
            GROUP BY `summary_length`, `source`, `billable_status`',
            false
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                if ($row['billable_status'] == ITrackingObject::NOT_BILLABLE) {
                    $not_billable += $row['row_count'];
                } else {
                    $billable += $row['row_count'];

                    if ($row['billable_status'] == ITrackingObject::BILLABLE) {
                        $to_be_invoiced += $row['row_count'];
                    } elseif ($row['billable_status'] == ITrackingObject::PENDING_PAYMENT) {
                        $pending_payment += $row['row_count'];
                    } elseif ($row['billable_status'] == ITrackingObject::PAID) {
                        $paid += $row['row_count'];
                    }
                }

                if (empty($summary_length[$row['source']])) {
                    $source[$row['source']] = 0;
                }

                $source[$row['source']] += $row['row_count'];

                if (empty($summary_length[$row['summary_length']])) {
                    $summary_length[$row['summary_length']] = 0;
                }

                $summary_length[$row['summary_length']] += $row['row_count'];
            }
        }

        $result = [
            'total' => $not_billable + $billable,
            'not_billable' => $not_billable,
            'billable' => $billable,
            'to_be_invoiced' => $to_be_invoiced,
            'pending_payment' => $pending_payment,
            'paid' => $paid,
            'invoiced' => $invoiced,
        ];

        if (!empty($source)) {
            $result['source'] = $source;
        }

        if (!empty($summary_length)) {
            $result['summary_length'] = $summary_length;
        }

        return $this->produceResult($result, $date);
    }
}
