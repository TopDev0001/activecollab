<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_not_required', SystemModule::NAME);

class PasswordRecoveryController extends AuthNotRequiredController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!AngieApplication::authentication()->getLoginPolicy()->isPasswordRecoveryEnabled()) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * Send reset password code.
     *
     * @return array|int
     */
    public function send_code(Request $request)
    {
        $username = $request->post('username');

        $user = is_valid_email($username) ? Users::findByEmail($username, true) : null;

        if ($user instanceof User && $user->isActive()) {
            return $user->beginPasswordRecovery();
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Verify code and reset user password.
     *
     * @return User|int
     */
    public function reset_password(Request $request)
    {
        $password = $request->post('password');

        if (mb_strlen($password) > 250) {
            return Response::BAD_REQUEST;
        }

        return Users::finishPasswordRecovery(
            $request->post('user_id'),
            $request->post('code'),
            $request->post('password')
        );
    }
}
