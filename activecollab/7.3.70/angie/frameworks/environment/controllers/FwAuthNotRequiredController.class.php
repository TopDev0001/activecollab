<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Authentication\Adapter\AdapterInterface;
use ActiveCollab\Authentication\AuthenticationResult\AuthenticationResultInterface;
use Angie\Controller\Controller;
use Angie\Http\Request;

/**
 * Auth not required controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwAuthNotRequiredController extends Controller
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof User && !$user->isActive()) {
            /** @var AdapterInterface $authentication_adapter */
            $authentication_adapter = $request->getAttribute('authentication_adapter');

            /** @var AuthenticationResultInterface $authenticated_with */
            $authenticated_with = $request->getAttribute('authenticated_with');

            if ($authentication_adapter && $authenticated_with) {
                return AngieApplication::authentication()->terminate($authentication_adapter, $authenticated_with);
            }
        }
    }
}
