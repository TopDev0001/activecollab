<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

function invoicing_handle_on_initial_settings(array &$settings): void
{
    $settings['default_tax_rate_id'] = TaxRates::getDefaultId();
    $settings['default_expense_category_id'] = ExpenseCategories::getDefaultId();
    $settings['invoice_second_tax_is_compound'] = ConfigOptions::getValue('invoice_second_tax_is_compound');
    $settings['invoice_second_tax_is_enabled'] = ConfigOptions::getValue('invoice_second_tax_is_enabled');
    $settings['default_accounting_app'] = ConfigOptions::getValue('default_accounting_app');
}
