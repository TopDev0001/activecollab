<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', SystemModule::NAME);

class QuickbooksInvoicesController extends AuthRequiredController
{
    /**
     * @var QuickbooksInvoice
     */
    protected $active_quickbooks_invoice;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof User && $user->isFinancialManager()) {
            $this->active_quickbooks_invoice = DataObjectPool::get('QuickbooksInvoice', $request->getId('quickbooks_invoice_id'));

            if (empty($this->active_quickbooks_invoice)) {
                $this->active_quickbooks_invoice = new QuickbooksInvoice();
            }
        } else {
            return Response::FORBIDDEN;
        }
    }

    /**
     * Display quickbooks invoices page.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        return QuickbooksInvoices::prepareCollection('active_remote_invoices', $user);
    }

    /**
     * Return quickbooks invoice.
     *
     * @return QuickbooksInvoice
     */
    public function view()
    {
        return $this->active_quickbooks_invoice;
    }

    /**
     * Create new quickbooks invoice.
     *
     * @return DataObject|int|StatusResponse
     */
    public function add(Request $request, User $user)
    {
        if (!QuickbooksInvoices::canAdd($user)) {
            return Response::FORBIDDEN;
        }

        try {
            return QuickbooksInvoices::create($request->post());
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Error occured during creation quickbooks invoice.',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e,
                ]
            );

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                [
                    'message' => lang('Failed to create invoice. Please contact our support.'),
                    'type' => 'error',
                ]
            );
        }
    }

    /**
     * Update quickbooks invoice.
     *
     * @return DataObject|int|StatusResponse
     */
    public function edit(Request $request, User $user)
    {
        if (!$this->active_quickbooks_invoice->isLoaded()) {
            return Response::NOT_FOUND;
        }

        try {
            return QuickbooksInvoices::update($this->active_quickbooks_invoice, $request->put());
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Error occured during update quickbooks invoice.',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e,
                ]
            );

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                [
                    'message' => lang('Failed to update invoice. Please contact our support.'),
                    'type' => 'error',
                ]
            );
        }
    }

    /**
     * Sync quickbooks invoices.
     *
     * @return QuickbooksInvoice[]|StatusResponse
     */
    public function sync(Request $request, User $user)
    {
        try {
            return QuickbooksInvoices::sync();
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Error occured during sync quickbooks invoices.',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e,
                ]
            );

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                [
                    'message' => lang('Failed to sync invoice. Please contact our support.'),
                    'type' => 'error',
                ]
            );
        }
    }
}
