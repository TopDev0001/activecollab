<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

abstract class FwPaymentsController extends AuthRequiredController
{
    /**
     * Active payment.
     *
     * @var Payment
     */
    protected $active_payment = false;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->isFinancialManager()) {
            return Response::NOT_FOUND;
        }

        $this->active_payment = DataObjectPool::get(Payment::class, $request->getId('payment_id'));

        if (empty($this->active_payment)) {
            $this->active_payment = new Payment();
        }

        return null;
    }

    /**
     * List all payments for the given object.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Payments::prepareCollection('recent_payments', $user);
    }

    /**
     * Add new payment.
     */
    public function add(Request $request)
    {
        $post = $request->post();

        if (empty($post)) {
            $post = [];
        }

        $post['type'] = Payment::class;
        $post['status'] = Payment::STATUS_PAID;

        return Payments::create($post);
    }

    /**
     * View loaded payment.
     *
     * @return int|Payment
     */
    public function view(Request $request, User $user)
    {
        return $this->active_payment->isLoaded() && $this->active_payment->canView($user)
            ? $this->active_payment
            : Response::NOT_FOUND;
    }

    /**
     * Update loaded payment.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_payment->isLoaded() && $this->active_payment->canEdit($user)
            ? Payments::update($this->active_payment, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Delete loaded payment.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_payment->isLoaded() && $this->active_payment->canDelete($user)
            ? Payments::scrap($this->active_payment)
            : Response::NOT_FOUND;
    }
}
