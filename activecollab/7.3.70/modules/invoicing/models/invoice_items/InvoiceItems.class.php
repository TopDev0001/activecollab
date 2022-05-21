<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * InvoiceItems class.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage models
 */
class InvoiceItems extends BaseInvoiceItems
{
    /**
     * Return number of items used by $tax_rate.
     *
     * @return int
     */
    public static function countByTaxRate(TaxRate $tax_rate)
    {
        $tax_rate_id = DB::escape($tax_rate->getId());

        return DB::executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM invoice_items WHERE first_tax_rate_id = $tax_rate_id OR second_tax_rate_id = $tax_rate_id");
    }
}
