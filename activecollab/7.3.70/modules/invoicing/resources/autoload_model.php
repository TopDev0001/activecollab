<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

AngieApplication::useModel(
    [
        'estimates',
        'invoice_item_templates',
        'invoice_items',
        'invoice_note_templates',
        'invoices',
        'recurring_profiles',
        'remote_invoice_items',
        'remote_invoices',
        'tax_rates',
    ],
    'invoicing'
);
