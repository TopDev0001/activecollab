<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', SystemModule::NAME);

class XeroInvoicesController extends AuthRequiredController
{
    /**
     * @var XeroInvoice
     */
    protected $active_xero_invoice;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof User && $user->isFinancialManager()) {
            $this->active_xero_invoice = DataObjectPool::get('XeroInvoice', $request->getId('xero_invoice_id'));

            if (empty($this->active_xero_invoice)) {
                $this->active_xero_invoice = new XeroInvoice();
            }
        } else {
            return Response::FORBIDDEN;
        }
    }

    /**
     * Display Xero invoices page.
     *
     * @return ModelCollection|null
     */
    public function index(Request $request, User $user)
    {
        return XeroInvoices::prepareCollection('active_remote_invoices', $user);
    }

    /**
     * Return Xero invoice.
     *
     * @return XeroInvoice
     */
    public function view()
    {
        return $this->active_xero_invoice;
    }

    /**
     * Create new Xero invoice.
     *
     * @return RemoteInvoice|int
     */
    public function add(Request $request, User $user)
    {
        return XeroInvoices::canAdd($user) ? XeroInvoices::create($request->post()) : Response::FORBIDDEN;
    }

    /**
     * Update Xero invoice.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_xero_invoice->isLoaded()
            ? XeroInvoices::update($this->active_xero_invoice, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Sync Xero invoices.
     *
     * @return XeroInvoice[]
     */
    public function sync(Request $request, User $user)
    {
        return XeroInvoices::sync($request->put('ids'));
    }
}
