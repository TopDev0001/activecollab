<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use DB;

final class RecurringInvoicesCollection extends Collection
{
    public function getValueFor(DateValue $date): ResultInterface
    {
        $enabled_recurring_invoices = DB::executeFirstCell(
            'SELECT COUNT(`id`) from recurring_profiles WHERE is_enabled = ?',
            true
        );

        $disabled_recurring_invoices = DB::executeFirstCell(
            'SELECT COUNT(`id`) from recurring_profiles WHERE is_enabled = ?',
            false
        );

        return $this->produceResult(
            [
                'total' => $enabled_recurring_invoices + $disabled_recurring_invoices,
                'by_status' => [
                    'enabled' => (int) $enabled_recurring_invoices,
                    'disabled' => (int) $disabled_recurring_invoices,
                ],
            ],
            $date
        );
    }
}
