<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class InternalRateController extends AuthRequiredController
{
    public function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user && !$user->isFinancialManager()) {
            return Response::FORBIDDEN;
        }
    }

    public function view(Request $request)
    {
        $userId = $request->get('user_id');

        if ($request->get('date')) {
            $rate = UserInternalRates::getByDate($userId, new DateValue($request->get('date')));
        } else {
            $rate = UserInternalRates::getCurrent($userId);
        }

        if (!$rate){
            return false;
        }

        return $rate;
    }

    public function index(Request $request, User $user)
    {
        return UserInternalRates::prepareCollection(
            sprintf('user_internal_rates_for_%d', (int) $request->get('user_id')),
            $user
        );
    }

    public function all(Request $request, User $user)
    {
        return UserInternalRates::prepareCollection(UserInternalRates::ALL, $user);
    }

    public function add(Request $request)
    {
        $data = $request->post();
        $data['valid_from'] = array_key_exists('valid_from', $data) ? $data['valid_from'] : (new DateValue('today'))->toMySql();
        $rate = UserInternalRates::getExistingHourlyRateByAttributes($data);

        if ($rate) {
            return UserInternalRates::update($rate, $data);
        }

        return UserInternalRates::create($data);
    }

    public function delete(Request $request)
    {
        $internal_rate = UserInternalRates::findById($request->getId());

        if (!$internal_rate) {
            return new StatusResponse(
                Response::NOT_FOUND,
                '',
                ['message' => lang('User internal rate not found.')]
            );
        }

        return UserInternalRates::scrap($internal_rate);
    }
}
