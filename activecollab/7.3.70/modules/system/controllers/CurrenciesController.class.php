<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class CurrenciesController extends AuthRequiredController
{
    /**
     * @var Currency
     */
    protected $active_currency;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_currency = DataObjectPool::get(Currency::class, $request->getId('currency_id'));

        if (empty($this->active_currency)) {
            $this->active_currency = new Currency();
        }

        return null;
    }

    /**
     * Show all available currencies.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Currencies::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * Create new currency.
     *
     * @return Currency|int
     */
    public function add(Request $request, User $user)
    {
        return Currencies::canAdd($user)
            ? Currencies::create($request->post())
            : Response::NOT_FOUND;
    }

    /**
     * Display currency details.
     *
     * @return Currency|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_currency->isLoaded() && $this->active_currency->canView($user)
            ? $this->active_currency
            : Response::NOT_FOUND;
    }

    /**
     * Update existing currency.
     *
     * @return Currency|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_currency->isLoaded() && $this->active_currency->canEdit($user)
            ? Currencies::update($this->active_currency, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Delete existing currency.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_currency->isLoaded() && $this->active_currency->canDelete($user)
            ? Currencies::scrap($this->active_currency)
            : Response::NOT_FOUND;
    }

    /**
     * @return Currency|int
     */
    public function view_default()
    {
        if ($currency = DataObjectPool::get(Currency::class, Currencies::getDefaultId())) {
            return $currency;
        }

        return Response::NOT_FOUND;
    }

    /**
     * @return Currency|int
     */
    public function set_default(Request $request, User $user)
    {
        if ($user->isOwner()) {
            /** @var Currency $currency */
            if ($currency = DataObjectPool::get(Currency::class, $request->put('currency_id'))) {
                return Currencies::setDefault($currency);
            }

            return Response::BAD_REQUEST;
        }

        return Response::NOT_FOUND;
    }
}
