<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\ExistingInvoiceNumbersResolver;

use DB;

class ExistingInvoiceNumbersResolver implements ExistingInvoiceNumbersResolverInterface
{
    public function getExistingInvoiceNumbers(): array
    {
        $existing_invoice_numbers = DB::executeFirstColumn('SELECT `number` FROM `invoices` ORDER BY `id`');

        if (empty($existing_invoice_numbers)) {
            $existing_invoice_numbers = [];
        }

        return $existing_invoice_numbers;
    }
}
