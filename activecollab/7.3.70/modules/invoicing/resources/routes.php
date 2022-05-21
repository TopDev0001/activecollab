<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

$this->mapResource('invoice_items');

$this->mapResource('invoices', [], function ($collection, $single) {
    $this->map("$collection[name]_archive", "$collection[path]/archive", ['controller' => $collection['controller'], 'action' => ['GET' => 'archive']], $collection['requirements']);
    $this->map("$collection[name]_private_notes", "$collection[path]/private-notes", ['controller' => $collection['controller'], 'action' => ['GET' => 'private_notes']], $collection['requirements']);
    $this->map("$collection[name]_preview_items", "$collection[path]/preview-items", ['controller' => $collection['controller'], 'action' => ['GET' => 'preview_items']], $collection['requirements']);
    $this->map("$collection[name]_projects", "$collection[path]/projects", ['controller' => $collection['controller'], 'action' => ['GET' => 'projects_invoicing_data']], $collection['requirements']);

    $this->map("$single[name]_send", "$single[path]/send", ['controller' => $single['controller'], 'action' => ['PUT' => 'send']], $single['requirements']);
    $this->map("$single[name]_export", "$single[path]/export", ['controller' => $single['controller'], 'action' => ['GET' => 'export']], $single['requirements']);
    $this->map("$single[name]_duplicate", "$single[path]/duplicate", ['controller' => $single['controller'], 'action' => ['POST' => 'duplicate']], $single['requirements']);
    $this->map("$single[name]_cancel", "$single[path]/cancel", ['controller' => $single['controller'], 'action' => ['PUT' => 'cancel']], $single['requirements']);
    $this->map("$single[name]_related_records", "$single[path]/related-records", ['controller' => $single['controller'], 'action' => ['DELETE' => 'release_related_records']], $single['requirements']);
    $this->map("$single[name]_mark_as_sent", "$single[path]/mark-as-sent", ['controller' => $single['controller'], 'action' => ['POST' => 'mark_as_sent']], $single['requirements']);
    $this->map("$single[name]_mark_zero_invoice_as_paid", "$single[path]/mark-zero-invoice-as-paid", ['controller' => $single['controller'], 'action' => ['POST' => 'mark_zero_invoice_as_paid']], $single['requirements']);
});

$this->map('invoice_public', 's/invoice', ['controller' => 'public_invoice', 'action' => ['GET' => 'view', 'PUT' => 'make_payment']]);

$this->mapResource('estimates', [], function ($collection, $single) {
    $this->map("$collection[name]_archive", "$collection[path]/archive", ['controller' => $collection['controller'], 'action' => ['GET' => 'archive']], $collection['requirements']);
    $this->map("$collection[name]_private_notes", "$collection[path]/private-notes", ['controller' => $collection['controller'], 'action' => ['GET' => 'private_notes']], $collection['requirements']);

    $this->map("$single[name]_send", "$single[path]/send", ['controller' => $single['controller'], 'action' => ['PUT' => 'send']], $single['requirements']);
    $this->map("$single[name]_export", "$single[path]/export", ['controller' => $single['controller'], 'action' => ['GET' => 'export']], $single['requirements']);
    $this->map("$single[name]_duplicate", "$single[path]/duplicate", ['controller' => $single['controller'], 'action' => ['POST' => 'duplicate']], $single['requirements']);
});

$this->mapResource('recurring_profiles', [], function ($collection, $single) {
    $this->map("$collection[name]_archive", "$collection[path]/archive", ['controller' => $collection['controller'], 'action' => ['GET' => 'archive']], $collection['requirements']);
    $this->map("$collection[name]_trigger", "$collection[path]/trigger", ['controller' => $collection['controller'], 'action' => ['POST' => 'trigger']], $collection['requirements']);
    $this->map("$single[name]_next_trigger_on", "$single[path]/next-trigger-on", ['controller' => $single['controller'], 'action' => ['GET' => 'next_trigger_on']], $single['requirements']);
});

$this->map('invoice_template', 'invoice-template', ['controller' => 'invoice_template', 'action' => ['GET' => 'show_settings', 'PUT' => 'save_settings']]);
$this->map('invoices_suggest_number', 'invoices/suggest-number', ['controller' => 'invoices', 'action' => ['GET' => 'suggest_number']]);

$this->mapResource('invoice_note_templates', null, function ($collection) {
    $this->map("$collection[name]_default", "$collection[path]/default", ['controller' => $collection['controller'], 'action' => ['GET' => 'view_default', 'PUT' => 'set_default', 'DELETE' => 'unset_default']], $collection['requirements']);
});

$this->mapResource('invoice_item_templates');

$this->mapResource('tax_rates', null, function ($collection) {
    $this->map("$collection[name]_default", "$collection[path]/default", ['controller' => $collection['controller'], 'action' => ['GET' => 'view_default', 'PUT' => 'set_default', 'DELETE' => 'unset_default']], $collection['requirements']);
});

$this->map('company_addresses_for_invoicing', 'companies/addresses-for-invoicing', ['controller' => 'company_addresses']);

// Quickbooks Integration
$this->map('quickbooks_payments', '/integrations/quickbooks/payments', ['controller' => 'quickbooks_integration', 'action' => ['GET' => 'sync_payments']]);
$this->map('quickbooks_request_url', '/integrations/quickbooks/request-url', ['controller' => 'quickbooks_integration', 'action' => ['GET' => 'get_request_url']]);
$this->map('quickbooks_authorize', '/integrations/quickbooks/authorize', ['controller' => 'quickbooks_integration', 'action' => ['PUT' => 'authorize']]);
$this->map('quickbooks_integration', '/integrations/quickbooks/data', ['controller' => 'quickbooks_integration', 'action' => ['GET' => 'get_data']]);
$this->map('quickbooks_integration_suggestion', '/integrations/quickbooks/suggestion', ['controller' => 'quickbooks_integration', 'action' => ['GET' => 'get_suggested_transaction_number']]);

// Quickbooks Invoices
$this->mapResource('quickbooks_invoices', ['collection_path' => '/quickbooks/invoices'], function ($collection, $single) {
    $this->map("$collection[name]_sync", "$collection[path]/sync", ['controller' => $collection['controller'], 'action' => ['PUT' => 'sync']], $collection['requirements']);
});

// Xero Integration
$this->map('xero_payments', '/integrations/xero/payments', ['controller' => 'xero_integration', 'action' => ['GET' => 'sync_payments']]);
$this->map('xero_request_url', '/integrations/xero/authorization-url', ['controller' => 'xero_integration', 'action' => ['GET' => 'get_authorization_url']]);
$this->map('xero_authorize', '/integrations/xero/authorize', ['controller' => 'xero_integration', 'action' => ['PUT' => 'authorize']]);
$this->map('xero_integration', '/integrations/xero/data', ['controller' => 'xero_integration', 'action' => ['GET' => 'get_data']]);

// Xero Invoices
$this->mapResource('xero_invoices', ['collection_path' => '/xero/invoices'], function ($collection, $single) {
    $this->map("$collection[name]_sync", "$collection[path]/sync", ['controller' => $collection['controller'], 'action' => ['PUT' => 'sync']], $collection['requirements']);
});
