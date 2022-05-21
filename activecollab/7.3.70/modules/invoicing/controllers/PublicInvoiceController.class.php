<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\FileDownload\FileDownload;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_not_required', SystemModule::NAME);

class PublicInvoiceController extends AuthNotRequiredController
{
    /**
     * Selected invoice.
     *
     * @var Invoice
     */
    protected $active_invoice;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $number = $request->get('number');
        $hash = $request->get('hash');

        if ($number && $hash) {
            $this->active_invoice = Invoices::findOne(
                [
                    'conditions' => ['number = ? AND hash = ?', $number, $hash],
                ]
            );
        }

        if (empty($this->active_invoice)) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * @return FileDownload|array
     */
    public function view(Request $request)
    {
        if ($request->get('download')) {
            return new FileDownload($this->active_invoice->exportToFile(), 'application/pdf', Invoices::getInvoicePdfName($this->active_invoice));
        }

        if ($recipient = $request->get('recipient')) {
            $values = explode(',', base64_decode($recipient)); // $values = 'john.doe@example.com,John Doe'

            if (count($values) === 2 && is_valid_email($values[0])) {
                if ($user = Users::findByEmail($values[0])) {
                    AccessLogs::logAccess($this->active_invoice, $user);
                } else {
                    AccessLogs::logAccess($this->active_invoice, new AnonymousUser($values[1], $values[0]));
                }
            }
        }

        return $this->getInvoiceAndPaymentFormSettings();
    }

    /**
     * @return array|int|StatusResponse
     */
    public function make_payment(Request $request)
    {
        /** @var PaymentGateway $gateway */
        $gateway = Payments::getCreditCardGateway($this->active_invoice);

        if (!$gateway instanceof PaymentGateway) {
            return Response::NOT_FOUND;
        }

        $amount = $request->put('amount');

        if ($amount < $this->active_invoice->getBalanceDue()) {
            return Response::BAD_REQUEST;
        }

        try {
            $this->active_invoice->payWithCreditCard(
                $amount,
                $request->put('name_on_card'),
                $request->put('card_number'),
                $request->put('expiration_month'),
                $request->put('expiration_year'),
                $request->put('security_code')
            );

            return $this->getInvoiceAndPaymentFormSettings(true);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'User payment invoice failed. Reason: {reason}',
                [
                    'gateway' => get_class($gateway),
                    'reason' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return new StatusResponse(400, 'Payment is not successfull. Please try again.');
        }
    }

    /**
     * Return invoice and payment settings.
     *
     * @param  bool  $reload_invoice
     * @return array
     */
    private function getInvoiceAndPaymentFormSettings($reload_invoice = false)
    {
        if ($reload_invoice) {
            $this->active_invoice = DataObjectPool::get('Invoice', $this->active_invoice->getId(), true);
        }

        /** @var PaymentGateway $payment_gateway */
        $payment_gateway = Payments::getCreditCardGateway($this->active_invoice);

        $accepted_payment_card = $this->active_invoice->isIssued() && $payment_gateway instanceof PaymentGateway;

        $credit_card_type = $accepted_payment_card ? $this->getCreditCardType($payment_gateway) : null;
        $token = null;
        if ($accepted_payment_card) {
            $token = $payment_gateway->getToken($this->active_invoice);
        }

        return [
            'invoice' => $this->active_invoice,
            'items' => $this->active_invoice->getItems(),
            'template' => new InvoiceTemplate(),
            'payments' => $this->active_invoice->getPayments(),
            'currency' => $this->active_invoice->getCurrency(),
            'creator' => $this->active_invoice->getCreatedBy(),
            'creator_company' => $this->active_invoice->getCreatedBy()->getCompany(),
            'accept_paypal' => $this->active_invoice->isIssued() && Payments::getPayPalGateway($this->active_invoice) instanceof PaymentGateway && Payments::getPayPalGateway($this->active_invoice)->getIsEnabled(),
            'accept_credit_card' => $accepted_payment_card,
            'credit_card_type' => $credit_card_type,
            'credit_card_token' => $token,
            'payflow_form_url' => ($credit_card_type == 'paypaldirect' || $credit_card_type == 'authorize') ? $payment_gateway->getFormUrl() : null,
            'is_currency_conflict' => $credit_card_type == 'paypaldirect' ? $this->active_invoice->getCurrency()->getCode() !== $payment_gateway->getProcessorCurrency() : false,
        ];
    }

    /**
     * Get credit card type.
     *
     * @param  PaymentGateway $payment_gateway
     * @return string
     */
    private function getCreditCardType($payment_gateway)
    {
        return strtolower(str_replace('Gateway', '', $payment_gateway->getType()));
    }
}
