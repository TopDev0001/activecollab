<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\Invoicing\Metric\InvoicesCollection;

function invoicing_handle_on_extra_stats(array &$stats, $date)
{
    (new InvoicesCollection(
        DB::getConnection(),
        function ($type) {
            return Integrations::findFirstByType($type);
        },
        function () {
            return XeroInvoices::countInvoicesStatus();
        },
        function () {
            return QuickbooksInvoices::countInvoicesStatus();
        }
    ))->getValueFor($date)->addTo($stats);
}
