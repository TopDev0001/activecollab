<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Tax Rates admin controller.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage controllers
 */
class TaxRatesController extends AuthRequiredController
{
    /**
     * Selected tax rate.
     *
     * @var TaxRate
     */
    protected $active_tax_rate;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_tax_rate = DataObjectPool::get('TaxRate', $request->getId('tax_rate_id'));
        if (empty($this->active_tax_rate)) {
            $this->active_tax_rate = new TaxRate();
        }
    }

    /**
     * Show all available tax rates.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return TaxRates::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * Create new tax rate.
     *
     * @return TaxRate|int
     */
    public function add(Request $request, User $user)
    {
        return TaxRates::canAdd($user) ? TaxRates::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * View tax rate.
     *
     * @return TaxRate|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_tax_rate->isLoaded() && $this->active_tax_rate->canView($user) ? $this->active_tax_rate : Response::NOT_FOUND;
    }

    /**
     * Update existing route.
     *
     * @return TaxRate|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_tax_rate->isLoaded() && $this->active_tax_rate->canEdit($user) ? TaxRates::update($this->active_tax_rate, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Delete existing tax rate.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_tax_rate->isLoaded() && $this->active_tax_rate->canDelete($user) ? TaxRates::scrap($this->active_tax_rate) : Response::NOT_FOUND;
    }

    /**
     * View default tax rate.
     *
     * @return TaxRate|int
     */
    public function view_default()
    {
        if ($tax_rate = TaxRates::getDefault()) {
            return $tax_rate;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Set default tax rate.
     *
     * @return TaxRate|int|null
     */
    public function set_default(Request $request)
    {
        /** @var TaxRate $tax_rate */
        if ($tax_rate = DataObjectPool::get('TaxRate', $request->put('tax_rate_id'))) {
            return TaxRates::setDefault($tax_rate);
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Unset default tax rate.
     *
     * @return int
     */
    public function unset_default()
    {
        return TaxRates::setDefault(null);
    }
}
